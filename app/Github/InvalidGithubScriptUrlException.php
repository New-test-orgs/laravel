<?php

namespace App\Github;

use Illuminate\Contracts\Debug\ShouldntReport;
use InvalidArgumentException;

class InvalidGithubScriptUrlException extends InvalidArgumentException implements ShouldntReport
{
    //
}
