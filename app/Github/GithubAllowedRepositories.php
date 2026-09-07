<?php

namespace App\Github;

class GithubAllowedRepositories
{
    public const OWNER = 'cart2cart';

    public const REPO = 'cart2cart-customization-laravel';

    /**
     * @var list<string>
     */
    private const ALLOWED_SLUGS = [
        self::OWNER.'/'.self::REPO,
        'New-test-orgs/laravel',
    ];

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
        $slug = strtolower($owner.'/'.$repo);

        foreach (self::ALLOWED_SLUGS as $allowedSlug) {
            if ($slug === strtolower($allowedSlug)) {
                return true;
            }
        }

        return false;
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
