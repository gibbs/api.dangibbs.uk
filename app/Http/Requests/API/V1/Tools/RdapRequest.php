<?php

namespace App\Http\Requests\API\V1\Tools;

use App\DataTransferObjects\V1\Tools\RdapData;
use Illuminate\Foundation\Http\FormRequest;

final class RdapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * The domain, IP, ASN, entity or URL to query
             *
             * @example google.com
             */
            'query' => ['required', 'string', 'between:1,258'],
            /**
             * The output format
             *
             * @default text
             *
             * @example json
             */
            'format' => ['nullable', 'string', 'in:text,whois,json,raw'],
            /**
             * The RDAP server to query, bypassing bootstrap discovery
             *
             * @example https://rdap.verisign.com/com/v1
             */
            'server' => ['nullable', 'string', 'between:1,258'],
            /**
             * The RDAP query type, normally auto-detected
             *
             * @default domain
             *
             * @example domain
             */
            'type' => ['nullable', 'string', 'in:autnum,domain,entity,help,ip,nameserver,url'],
            /**
             * Specific top-level fields to output
             *
             * @example ["handle","status"]
             */
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string'],
        ];
    }

    public function toDto(): RdapData
    {
        return new RdapData(
            query: $this->validated('query'),
            verbose: false,
            format: $this->validated('format') ?? 'text',
            server: $this->validated('server'),
            type: $this->validated('type'),
            fields: $this->validated('fields') ?? [],
            timeout: config('api.rdap.timeout'),
            insecure: config('api.rdap.insecure'),
            experimental: config('api.rdap.experimental'),
        );
    }
}
