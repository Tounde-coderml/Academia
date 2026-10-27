<?php

namespace App\Http\Requests;

use App\Models\FiliereNiveauMatiere;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFiliereNiveauMatiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('academic_referentials.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'filiere_id' => ['required', 'integer', Rule::exists('filieres', 'id')->where('is_active', true)],
            'niveau_id' => ['required', 'integer', Rule::exists('niveaux', 'id')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'filiere_id.exists' => 'La filière sélectionnée est introuvable ou inactive.',
            'niveau_id.exists' => 'Le niveau sélectionné est introuvable ou inactif.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $exists = FiliereNiveauMatiere::query()
                    ->where('filiere_id', $this->integer('filiere_id'))
                    ->where('niveau_id', $this->integer('niveau_id'))
                    ->where('matiere_id', $this->route('matiere')?->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('filiere_id', 'Cette association existe déjà.');
                }
            },
        ];
    }
}
