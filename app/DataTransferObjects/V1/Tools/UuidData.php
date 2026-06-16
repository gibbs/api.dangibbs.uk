<?php

namespace App\DataTransferObjects\V1\Tools;

readonly class UuidData
{
    public function __construct(
        public readonly bool $random,
        public readonly bool $time,
    ) {}

    public function toArray(): array
    {
        return [
            'random' => $this->random,
            'time' => $this->time,
        ];
    }
}
