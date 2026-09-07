<?php

namespace App\Rules\Github;

use App\Github\GithubCommitResolver;
use App\Github\GithubScriptReference;
use App\Github\GithubUrlParser;
use App\Github\InvalidGithubScriptUrlException;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class GithubScriptUrl implements ValidationRule
{
    public ?GithubScriptReference $reference = null;

    public function __construct(
        private GithubUrlParser $parser,
        private GithubCommitResolver $resolver,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Enter a GitHub file or folder permalink for a specific commit.');

            return;
        }

        try {
            $this->reference = $this->resolver->resolve($this->parser->parse($value));
        } catch (InvalidGithubScriptUrlException $exception) {
            $fail($exception->getMessage());
        }
    }
}
