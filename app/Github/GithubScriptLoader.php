<?php

namespace App\Github;

use App\Scripts\MigrationScript;

class GithubScriptLoader
{
    public function load(string $path): MigrationScript
    {
        $script = require $path;

        if (! $script instanceof MigrationScript) {
            throw new GithubScriptFetchException('The GitHub file must return a MigrationScript instance.');
        }

        return $script;
    }
}
