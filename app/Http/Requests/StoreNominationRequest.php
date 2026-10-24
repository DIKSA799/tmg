<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNominationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('ambassador') !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'statement' => ['nullable', 'string', 'max:280'],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'accept_terms.accepted' => 'Please accept the nomination terms to continue.',
        ];
    }
}
