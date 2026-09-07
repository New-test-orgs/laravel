<?php

namespace App\Github;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GithubScriptFetcher
{
    private const MAX_BYTES = 1_048_576;

    public function fetch(GithubScriptReference $reference): string
    {
        if ($reference->kind !== GithubObjectKind::Blob) {
            throw new GithubScriptFetchException('Only a single GitHub file can be downloaded.');
        }

        $diskPath = $this->diskPath($reference);

        if (Storage::disk('local')->exists($diskPath)) {
            return Storage::disk('local')->path($diskPath);
        }

        Cache::lock('github-script-'.$reference->sha.'-'.hash('sha256', $reference->path), 30)
            ->block(15, function () use ($reference, $diskPath): void {
                if (Storage::disk('local')->exists($diskPath)) {
                    return;
                }

                Storage::disk('local')->put($diskPath, $this->download($reference));
            });

        return Storage::disk('local')->path($diskPath);
    }

    private function download(GithubScriptReference $reference): string
    {
        try {
            $payload = Http::github()
                ->retry([100, 500, 1000], 0, function (Throwable $exception): bool {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && ($exception->response->serverError() || $exception->response->status() === 429));
                })
                ->throw()
                ->get($this->contentsUrl($reference), ['ref' => $reference->sha])
                ->json();
        } catch (ConnectionException) {
            throw new GithubScriptFetchException('GitHub could not download that file. Try again.');
        } catch (RequestException $exception) {
            if ($exception->response->notFound() || $exception->response->status() === 422) {
                throw new GithubScriptFetchException('That file does not exist at the given commit.');
            }

            throw new GithubScriptFetchException('GitHub could not download that file. Try again.');
        }

        if (! is_array($payload) || ($payload['type'] ?? null) !== 'file') {
            throw new GithubScriptFetchException('The GitHub permalink must point to a file.');
        }

        $size = $payload['size'] ?? null;

        if (! is_int($size) || $size > self::MAX_BYTES) {
            throw new GithubScriptFetchException('The GitHub file is larger than 1 MB.');
        }

        if (($payload['encoding'] ?? null) !== 'base64' || ! is_string($payload['content'] ?? null)) {
            throw new GithubScriptFetchException('GitHub did not return file contents.');
        }

        $contents = base64_decode($payload['content'], true);

        if ($contents === false || strlen($contents) > self::MAX_BYTES) {
            throw new GithubScriptFetchException('GitHub did not return file contents.');
        }

        return $contents;
    }

    private function contentsUrl(GithubScriptReference $reference): string
    {
        $encodedPath = implode('/', array_map(rawurlencode(...), explode('/', $reference->path)));

        return "/repos/{$reference->owner}/{$reference->repo}/contents/{$encodedPath}";
    }

    private function diskPath(GithubScriptReference $reference): string
    {
        return 'scripts/'.$reference->sha.'/'.$reference->path;
    }
}
