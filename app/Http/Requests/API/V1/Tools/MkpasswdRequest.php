<?php

namespace App\Http\Requests\API\V1\Tools;

use App\DataTransferObjects\V1\Tools\MkpasswdData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MkpasswdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $method = (string) $this->input('method');

        return [
            // @example Password123
            'input' => ['required', 'alpha_num', 'string', 'between:1,64'],

            // @example sha256crypt
            'method' => ['required', 'string', 'in:sha512crypt,sha256crypt,scrypt,md5crypt'],

            /**
             * Not available for MD5.
             * Must be 0, 6, 7, 8, 9, 10 or 11 for scrypt.
             * Must be between 1000 and 1000000 for SHA.
             *
             * @example 1000
             */
            'rounds' => Rule::when(
                $method === 'scrypt',
                ['nullable', 'in:0,6,7,8,9,10,11'],
                ['integer', 'numeric', 'between:0,1000000']
            ),

            /**
             * Not available for scrypt.
             * Must be between 8-16 characters for SHA.
             * Must be 8 characters for MD5.
             *
             * @example ABCDEFGH
             */
            'salt' => match (true) {
                in_array($method, ['sha512crypt', 'sha256crypt']) => ['nullable', 'alpha_num', 'between:8,16'],
                $method === 'md5crypt' => ['nullable', 'alpha_num', 'size:8'],
                $method === 'scrypt' => ['nullable', 'prohibited'],
                default => ['nullable'],
            },
        ];
    }

    public function toDto(): MkpasswdData
    {
        return new MkpasswdData(
            input: $this->validated('input'),
            salt: $this->validated('salt'),
            method: $this->validated('method'),
            rounds: $this->validated('rounds'),
        );
    }
}
