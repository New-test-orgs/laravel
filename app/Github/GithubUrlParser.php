<?php

namespace App\Github;

class GithubUrlParser
{
    /**
     * @var list<string>
     */
    private const ALLOWED_HOSTS = ['github.com', 'www.github.com'];

    public function __construct(private GithubAllowedRepositories $allowedRepositories) {}

    public function parse(string $url): GithubScriptReference
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'], $parts['path'])) {
            throw new InvalidGithubScriptUrlException('Enter a GitHub file permalink for a specific commit.');
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
            throw new InvalidGithubScriptUrlException('Enter a GitHub file permalink for a specific commit.');
        }

        [$owner, $repo, $kindSegment, $ref] = $segments;

        $kind = GithubObjectKind::tryFrom(strtolower($kindSegment));

        if ($kind === GithubObjectKind::Tree) {
            throw new InvalidGithubScriptUrlException('Paste a GitHub file permalink, not a folder.');
        }

        if ($kind !== GithubObjectKind::Blob) {
            throw new InvalidGithubScriptUrlException('The URL must be a GitHub file permalink.');
        }

        $sha = $this->normalizedRef($ref);

        $path = $this->normalizedPath(array_slice($segments, 4));

        if ($path === '') {
            throw new InvalidGithubScriptUrlException('A file permalink must include a path after the commit, branch, or tag.');
        }

        if (! $this->isAllowedRepository($owner, $repo)) {
            throw new InvalidGithubScriptUrlException('That repository is not on the allow-list.');
        }

        return new GithubScriptReference(
            owner: $owner,
            repo: $repo,
            sha: $sha,
            path: $path,
            kind: $kind,
            url: $url,
        );
    }

    private function normalizedRef(string $ref): string
    {
        if (preg_match('/^[0-9a-f]{7,40}$/i', $ref) === 1) {
            return strtolower($ref);
        }

        if (preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9._-]*[A-Za-z0-9])?$/', $ref) !== 1) {
            throw new InvalidGithubScriptUrlException('The URL must include a commit SHA, branch, or tag.');
        }

        return $ref;
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
        return $this->allowedRepositories->contains($owner, $repo);
    }
}
