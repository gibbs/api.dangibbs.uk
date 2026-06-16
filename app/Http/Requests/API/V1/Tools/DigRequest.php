<?php

namespace App\Http\Requests\API\V1\Tools;

use App\DataTransferObjects\V1\Tools\DigData;
use App\Rules\API\V1\Tools\DigDnsType;
use Illuminate\Foundation\Http\FormRequest;

final class DigRequest extends FormRequest
{
    /**
     * Valid nameservers
     */
    private $nameservers = [
        'cloudflare',
        'google',
        'quad9',
        'opendns',
        'comodo',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Nameserver list
        $nameservers = implode(',', $this->nameservers);

        return [
            /**
             * The hostname to query
             *
             * @example google.com
             */
            'name' => ['required_without:query', 'string', 'between:1,258'],
            /** @ignoreParam */
            'query' => ['required_without:name', 'string', 'between:1,258'],
            /**
             * The nameserver to query against
             */
            'nameserver' => ['required', 'string', 'in:'.$nameservers],
            /**
             * The DNS record(s) to query
             *
             * @example ["A","AAAA","CNAME"]
             */
            'types' => ['nullable', 'array'],
            'types.*' => ['string', new DigDnsType],
        ];
    }

    public function toDto(): DigData
    {
        return new DigData(
            name: $this->validated('name') ?? $this->validated('query'),
            nameserver: $this->validated('nameserver'),
            types: $this->validated('types') ?? []
        );
    }
}
