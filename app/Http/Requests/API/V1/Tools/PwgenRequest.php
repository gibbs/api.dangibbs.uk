<?php

namespace App\Http\Requests\API\V1\Tools;

use App\DataTransferObjects\V1\Tools\PwgenData;
use Illuminate\Foundation\Http\FormRequest;

final class PwgenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Do not include numbers in the generated passwords
             *
             * @default false
             */
            'no-numerals' => ['nullable', 'boolean'],
            /**
             * Stops passwords including capitalisation
             *
             * @default false
             */
            'no-capitalize' => ['nullable', 'boolean'],
            /**
             * Do not characters that could be confused when printed, such as 'l' and '1', or '0' or 'O'
             *
             * @default false
             */
            'ambiguous' => ['nullable', 'boolean'],
            /**
             * Include at least one capital letter in each password
             *
             * @default false
             *
             * @example true
             */
            'capitalize' => ['nullable', 'boolean'],
            /**
             * The number of passwords to generate
             *
             * @example 5
             */
            'num-passwords' => ['required', 'integer', 'between:1,100'],
            /**
             * Include at least one number in each password
             *
             * @default false
             *
             * @example true
             */
            'numerals' => ['nullable', 'boolean'],
            /**
             * Characters to be ignored/removed
             *
             * @default
             */
            'remove-chars' => ['nullable', 'string', 'between:1,65'],
            /**
             * Completely randomise output (hard-to-memorise)
             *
             * @default false
             *
             * @example true
             */
            'secure' => ['nullable', 'boolean'],
            /**
             * Do not include vowels or numbers that might be mistaken for vowels
             *
             * @default false
             */
            'no-vowels' => ['nullable', 'boolean'],
            /**
             * Include at least one special character in the password
             *
             * @default false
             *
             * @example true
             */
            'symbols' => ['nullable', 'boolean'],
            /**
             * The password length
             *
             * @example 32
             */
            'length' => ['required', 'integer', 'between:1,8192'],
        ];
    }

    public function toDto(): PwgenData
    {
        return new PwgenData(
            noNumerals: $this->boolean('no-numerals'),
            noCapitalize: $this->boolean('no-capitalize'),
            ambiguous: $this->boolean('ambiguous'),
            capitalize: $this->boolean('capitalize'),
            numPasswords: $this->validated('num-passwords'),
            numerals: $this->boolean('numerals'),
            removeChars: $this->string('remove-chars'),
            secure: $this->boolean('secure'),
            noVowels: $this->boolean('no-vowels'),
            symbols: $this->boolean('symbols'),
            length: $this->validated('length'),
        );
    }
}
