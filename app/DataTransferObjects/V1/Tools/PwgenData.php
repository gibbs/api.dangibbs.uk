<?php

namespace App\DataTransferObjects\V1\Tools;

readonly class PwgenData
{
    public function __construct(
        public readonly bool $noNumerals,
        public readonly bool $noCapitalize,
        public readonly bool $ambiguous,
        public readonly bool $capitalize,
        public readonly int $numPasswords,
        public readonly bool $numerals,
        public readonly string $removeChars,
        public readonly bool $secure,
        public readonly bool $noVowels,
        public readonly bool $symbols,
        public readonly int $length,
    ) {}

    public function toArray(): array
    {
        return [
            'no-numerals' => $this->noNumerals,
            'no-capitalize' => $this->noCapitalize,
            'ambiguous' => $this->ambiguous,
            'capitalize' => $this->capitalize,
            'num-passwords' => $this->numPasswords,
            'numerals' => $this->numerals,
            'remove-chars' => $this->removeChars,
            'secure' => $this->secure,
            'no-vowels' => $this->noVowels,
            'symbols' => $this->symbols,
            'length' => $this->length,
        ];
    }
}
