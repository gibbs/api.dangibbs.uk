<?php

namespace App\DataTransferObjects\V1\Tools;

readonly class MkpasswdData
{
    public function __construct(
        public readonly string $input,
        public readonly ?string $salt,
        public readonly string $method,
        public readonly ?int $rounds,
    ) {}

    public function toArray(): array
    {
        return [
            'input' => $this->input,
            'salt' => $this->salt,
            'method' => $this->method,
            'rounds' => $this->rounds,
        ];
    }
}
