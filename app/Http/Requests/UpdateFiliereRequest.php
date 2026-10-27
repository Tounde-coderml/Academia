<?php

namespace App\Http\Requests;

use App\Models\Filiere;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFiliereRequest extends ReferentielRequest
{
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('filieres', 'code')->ignore($this->route('filiere')),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $filiere = $this->route('filiere');
                $code = $this->input('code');

                if (
                    ! $validator->errors()->has('code')
                    && $filiere instanceof Filiere
                    && $filiere->isUsed()
                    && is_string($code)
                    && trim($code) !== $filiere->code
                ) {
                    $validator->errors()->add('code', 'Le code ne peut plus être modifié après utilisation.');
                }
            },
        ];
    }
}
