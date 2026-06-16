<?php

namespace App\Rules\API\V1\Tools;

use Closure;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type as OpenApiType;
use Illuminate\Contracts\Validation\ValidationRule;

class DigDnsType implements ValidationRule
{
    private array $types = [
        'a',
        'aaaa',
        'any',
        'caa',
        'cname',
        'dnskey',
        'ds',
        'mx',
        'ns',
        'ptr',
        'soa',
        'srv',
        'tlsa',
        'tsig',
        'txt',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array(strtolower($value), $this->types, true)) {
            $fail("The $attribute is invalid.");
        }
    }

    public function docs(OpenApiType $prevType): OpenApiType
    {
        return (new StringType)->enum($this->types);
    }
}
