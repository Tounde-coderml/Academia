<?php

namespace App\Http\Requests;

use App\Models\Niveau;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateNiveauRequest extends ReferentielRequest
{
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('niveaux', 'code')->ignore($this->route('niveau')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $niveau = $this->route('niveau');
                $code = $this->input('code');

                if (
                    ! $validator->errors()->has('code')
                    && $niveau instanceof Niveau
                    && $niveau->isUsed()
                    && is_string($code)
                    && trim($code) !== $niveau->code
                ) {
                    $validator->errors()->add('code', 'Le code ne peut plus être modifié après utilisation.');
                }
            },
        ];
    }
}
