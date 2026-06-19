<?php

namespace App\DataTransferObjects\V1\Tools;

readonly class QrcodeData
{
    public function __construct(
        public readonly string $input,
        public readonly ?string $background,
        public readonly ?bool $casesensitive,
        public readonly ?int $dpi,
        public readonly ?string $foreground,
        public readonly ?bool $ignorecase,
        public readonly ?string $level,
        public readonly ?int $margin,
        public readonly ?int $size,
        public readonly string $type,
    ) {}

    public function toArray(): array
    {
        return [
            'input' => $this->input,
            'background' => $this->background,
            'casesensitive' => $this->casesensitive,
            'dpi' => $this->dpi,
            'foreground' => $this->foreground,
            'ignorecase' => $this->ignorecase,
            'level' => strtoupper($this->level),
            'margin' => $this->margin,
            'size' => $this->size,
            'type' => strtoupper($this->type),
        ];
    }
}
