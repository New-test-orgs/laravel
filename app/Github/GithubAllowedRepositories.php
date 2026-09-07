<?php

namespace App\Github;

class GithubAllowedRepositories
{
    public const OWNER = 'cart2cart';

    public const REPO = 'cart2cart-customization-laravel';

    public static function slug(): string
    {
        return self::OWNER.'/'.self::REPO;
    }

    public static function url(): string
    {
        return 'https://github.com/'.self::slug();
    }

    public function contains(string $owner, string $repo): bool
    {
        return strtolower($owner) === strtolower(self::OWNER)
            && strtolower($repo) === strtolower(self::REPO);
    }

    public function primaryUrl(): string
    {
        return self::url();
    }

    public function examplePermalink(): string
    {
        return self::url().'/blob/a1b2c3d/scripts/demo.php';
    }
}
