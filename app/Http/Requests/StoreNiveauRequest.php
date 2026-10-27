<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreNiveauRequest extends ReferentielRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('niveaux', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé.',
            'name.required' => 'L’intitulé est obligatoire.',
            'sort_order.integer' => 'L’ordre doit être un nombre entier.',
            'sort_order.min' => 'L’ordre ne peut pas être négatif.',
            'sort_order.max' => 'L’ordre est trop élevé.',
        ];
    }
}
