<?php

namespace App\Http\Requests;

use App\Models\Matiere;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMatiereRequest extends ReferentielRequest
{
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('matieres', 'code')->ignore($this->route('matiere')),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $matiere = $this->route('matiere');
                $code = $this->input('code');

                if (
                    ! $validator->errors()->has('code')
                    && $matiere instanceof Matiere
                    && $matiere->isUsed()
                    && is_string($code)
                    && trim($code) !== $matiere->code
                ) {
                    $validator->errors()->add('code', 'Le code ne peut plus être modifié après utilisation.');
                }
            },
        ];
    }
}
