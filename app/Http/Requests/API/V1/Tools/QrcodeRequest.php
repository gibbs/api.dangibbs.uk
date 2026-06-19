<?php

namespace App\Http\Requests\API\V1\Tools;

use App\DataTransferObjects\V1\Tools\QrcodeData;
use Illuminate\Foundation\Http\FormRequest;

final class QrcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * QR code input data
             *
             * @example https://google.com
             */
            'input' => 'required|string|max:2048',
            /**
             * specify the type of the generated image
             *
             * @default png
             */
            'type' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if (! in_array(strtoupper($value), ['PNG', 'SVG', 'EPS', 'ASCII', 'XPM'])) {
                    $fail("The selected {$attribute} is invalid.");
                }
            }],
            /**
             * specify error correction level from L (lowest) to H (highest)
             *
             * @default L
             */
            'level' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if (! in_array(strtoupper($value), ['L', 'M', 'Q', 'H'])) {
                    $fail("The selected {$attribute} is invalid.");
                }
            }],
            /**
             * specify the width of margin
             *
             * @default 4
             */
            'margin' => ['nullable', 'integer', 'between:0,20'],
            /**
             * specify the DPI of the generated PNG
             *
             * @default 72
             */
            'dpi' => ['nullable', 'integer', 'between:72,1200'],
            /**
             * specify background colour in hexadecimal notation
             *
             * @default FFFFFF
             */
            'background' => ['nullable', 'string', 'regex:/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            /**
             * specify foreground colour in hexadecimal notation
             *
             * @default 000000
             */
            'foreground' => ['nullable', 'string', 'regex:/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            /**
             * the size of dot (pixel)
             *
             * @default 3
             */
            'size' => 'nullable|integer|between:1,50',
            /**
             * ignore case distinctions and use only upper-case characters
             *
             * @default false
             */
            'ignorecase' => 'nullable|boolean',
            /**
             * encode lower-case alphabet characters in 8-bit mode
             *
             * @default true
             */
            'casesensitive' => 'nullable|boolean',
        ];
    }

    public function toDto(): QrcodeData
    {
        return new QrcodeData(
            input: $this->validated('input', ''),
            background: $this->validated('background', null),
            casesensitive: $this->boolean('casesensitive'),
            dpi: $this->integer('dpi'),
            foreground: $this->string('foreground', null),
            ignorecase: $this->boolean('ignorecase'),
            level: $this->validated('level'),
            margin: $this->integer('margin'),
            size: $this->integer('size'),
            type: $this->string('type'),
        );
    }
}
