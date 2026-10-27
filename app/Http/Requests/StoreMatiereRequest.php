<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreMatiereRequest extends ReferentielRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('matieres', 'code')],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code est déjà utilisé.',
            'name.required' => 'L’intitulé est obligatoire.',
        ];
    }
}
