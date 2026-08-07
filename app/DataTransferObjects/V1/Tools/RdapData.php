<?php

namespace App\DataTransferObjects\V1\Tools;

readonly class RdapData
{
    public function __construct(
        public string $query,
        public bool $verbose,
        public string $format,
        public ?string $server,
        public ?string $type,
        public array $fields,
        public int $timeout,
        public bool $insecure,
        public bool $experimental,
    ) {}

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'verbose' => $this->verbose,
            'timeout' => $this->timeout,
            'insecure' => $this->insecure,
            'experimental' => $this->experimental,
            'format' => $this->format,
            'server' => $this->server,
            'type' => $this->type,
            'fields' => $this->fields,
        ];
    }
}
