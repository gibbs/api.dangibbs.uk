<?php

namespace App\Http\Requests\API\V1\Tools;

use App\DataTransferObjects\V1\Tools\UuidData;
use Illuminate\Foundation\Http\FormRequest;

final class UuidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * random-based created mostly of random bits
             *
             * @default false
             */
            'random' => 'nullable|boolean',
            /**
             * time-based based on the remote system clock
             *
             * @default false
             */
            'time' => 'nullable|boolean',
        ];
    }

    public function toDto(): UuidData
    {
        return new UuidData(
            random: $this->boolean('random'),
            time: $this->boolean('time'),
        );
    }
}
