<?php

namespace App\Github;

final readonly class GithubScriptReference
{
    public function __construct(
        public string $owner,
        public string $repo,
        public string $sha,
        public string $path,
        public GithubObjectKind $kind,
        public string $url,
    ) {}

    public function repository(): string
    {
        return $this->owner.'/'.$this->repo;
    }

    public function withSha(string $sha): self
    {
        return new self(
            owner: $this->owner,
            repo: $this->repo,
            sha: $sha,
            path: $this->path,
            kind: $this->kind,
            url: $this->url,
        );
    }
}
