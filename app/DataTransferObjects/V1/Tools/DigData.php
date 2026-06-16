<?php

namespace App\DataTransferObjects\V1\Tools;

readonly class DigData
{
    public function __construct(
        public readonly string $name,
        public readonly string $nameserver,
        public readonly array $types
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'nameserver' => $this->nameserver,
            'types' => $this->getFormattedTypes(),
        ];
    }

    /**
     * Transform types array into the required structure
     */
    public function getFormattedTypes(): array
    {
        return array_fill_keys($this->types, true);
    }
}
