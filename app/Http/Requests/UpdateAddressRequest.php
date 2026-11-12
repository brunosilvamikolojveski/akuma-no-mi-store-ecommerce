<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('state'))) {
            $this->merge(['state' => strtoupper($this->input('state'))]);
        }
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'required', 'string', 'max:50'],
            'recipient_name' => ['sometimes', 'required', 'string', 'max:120'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:9', 'regex:/^\d{5}-?\d{3}$/'],
            'state' => ['sometimes', 'required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'neighborhood' => ['sometimes', 'required', 'string', 'max:100'],
            'street' => ['sometimes', 'required', 'string', 'max:160'],
            'number' => ['sometimes', 'required', 'string', 'max:20'],
            'complement' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'postal_code.required' => 'Informe o CEP.',
            'postal_code.string' => 'O CEP deve ser informado como texto.',
            'postal_code.max' => 'O CEP deve ter no máximo 9 caracteres.',
            'postal_code.regex' => 'Informe um CEP válido no formato 00000-000.',
        ];
    }
}
