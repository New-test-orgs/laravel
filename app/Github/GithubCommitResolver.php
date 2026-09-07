<?php

namespace App\Github;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class GithubCommitResolver
{
    public function resolve(GithubScriptReference $reference): GithubScriptReference
    {
        try {
            $sha = Http::github()
                ->retry([100, 500, 1000], 0, function (Throwable $exception): bool {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && ($exception->response->serverError() || $exception->response->status() === 429));
                })
                ->throw()
                ->get("/repos/{$reference->owner}/{$reference->repo}/commits/".rawurlencode($reference->sha))
                ->json('sha');
        } catch (ConnectionException) {
            throw new InvalidGithubScriptUrlException('GitHub could not resolve that commit. Try again.');
        } catch (RequestException $exception) {
            if ($exception->response->notFound() || $exception->response->status() === 422) {
                throw new InvalidGithubScriptUrlException('That commit, branch, or tag does not exist in the allowed repository.');
            }

            throw new InvalidGithubScriptUrlException('GitHub could not resolve that commit. Try again.');
        }

        if (! is_string($sha) || preg_match('/^[0-9a-f]{40}$/', $sha) !== 1) {
            throw new InvalidGithubScriptUrlException('GitHub did not return a full commit SHA.');
        }

        return $reference->withSha($sha);
    }
}
