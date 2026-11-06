<?php

namespace App\Http\Requests;

use App\Models\FiliereNiveauMatiere;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEpreuveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('exams.submit') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'filiere_id' => ['required', 'integer', Rule::exists('filieres', 'id')->where('is_active', true)],
            'niveau_id' => ['required', 'integer', Rule::exists('niveaux', 'id')->where('is_active', true)],
            'matiere_id' => ['required', 'integer', Rule::exists('matieres', 'id')->where('is_active', true)],
            'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || preg_match('/^\d{4}-\d{4}$/', $value) !== 1) {
                    return;
                }

                [$start, $end] = array_map('intval', explode('-', $value));
                if ($end !== $start + 1) {
                    $fail('L’année scolaire doit contenir deux années consécutives.');
                }
            }],
            'semester' => ['required', 'integer', Rule::in([1, 2])],
            'type' => ['required', Rule::in(['examen', 'rattrapage', 'devoir', 'controle_continu'])],
            'file' => ['required', 'file', 'mimes:pdf,doc,jpg,jpeg,png', 'extensions:pdf,doc,jpg,jpeg,png', 'max:20480'],
            'duplicate_confirmed' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['filiere_id', 'niveau_id', 'matiere_id'])) {
                return;
            }

            $associationExists = FiliereNiveauMatiere::query()
                ->where('filiere_id', $this->integer('filiere_id'))
                ->where('niveau_id', $this->integer('niveau_id'))
                ->where('matiere_id', $this->integer('matiere_id'))
                ->whereHas('filiere', fn ($query) => $query->where('is_active', true))
                ->whereHas('niveau', fn ($query) => $query->where('is_active', true))
                ->whereHas('matiere', fn ($query) => $query->where('is_active', true))
                ->exists();

            if (! $associationExists) {
                $validator->errors()->add('matiere_id', 'Cette matière n’est pas associée à cette filière et à ce niveau.');
            }
        }];
    }
}
