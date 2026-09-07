<?php

namespace App\Github;

class GithubUrlParser
{
    /**
     * @var list<string>
     */
    private const ALLOWED_HOSTS = ['github.com', 'www.github.com'];

    public function parse(string $url): GithubScriptReference
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'], $parts['path'])) {
            throw new InvalidGithubScriptUrlException('Enter a GitHub file or folder permalink for a specific commit.');
        }

        $host = strtolower($parts['host']);

        if (! in_array($host, self::ALLOWED_HOSTS, true)) {
            throw new InvalidGithubScriptUrlException('The URL must point to github.com.');
        }

        $segments = array_values(array_filter(
            explode('/', $parts['path']),
            fn (string $segment): bool => $segment !== '',
        ));

        if (count($segments) < 4) {
            throw new InvalidGithubScriptUrlException('Enter a GitHub file or folder permalink for a specific commit.');
        }

        [$owner, $repo, $kindSegment, $sha] = $segments;

        $kind = GithubObjectKind::tryFrom(strtolower($kindSegment));

        if ($kind === null) {
            throw new InvalidGithubScriptUrlException('The URL must be a GitHub blob or tree permalink.');
        }

        if (preg_match('/^[0-9a-f]{7,40}$/i', $sha) !== 1) {
            throw new InvalidGithubScriptUrlException('The URL must include a commit SHA, not a branch or tag name.');
        }

        $path = $this->normalizedPath(array_slice($segments, 4));

        if ($kind === GithubObjectKind::Blob && $path === '') {
            throw new InvalidGithubScriptUrlException('A file permalink must include a path after the commit SHA.');
        }

        if (! $this->isAllowedRepository($owner, $repo)) {
            throw new InvalidGithubScriptUrlException('That repository is not on the allow-list.');
        }

        return new GithubScriptReference(
            owner: $owner,
            repo: $repo,
            sha: strtolower($sha),
            path: $path,
            kind: $kind,
            url: $url,
        );
    }

    /**
     * @param  list<string>  $segments
     */
    private function normalizedPath(array $segments): string
    {
        $decoded = array_map(rawurldecode(...), $segments);

        foreach ($decoded as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new InvalidGithubScriptUrlException('The GitHub path is not valid.');
            }
        }

        return implode('/', $decoded);
    }

    private function isAllowedRepository(string $owner, string $repo): bool
    {
        $candidate = strtolower($owner.'/'.$repo);

        foreach (config('services.github.allowed_repositories') as $allowed) {
            if (strtolower((string) $allowed) === $candidate) {
                return true;
            }
        }

        return false;
    }
}
