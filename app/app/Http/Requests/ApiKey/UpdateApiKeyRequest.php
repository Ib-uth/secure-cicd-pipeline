<?php

namespace App\Http\Requests\ApiKey;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => ['string', 'in:read,write,delete'],
        ];
    }
}
