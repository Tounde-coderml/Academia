<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogueSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'filiere_id' => ['nullable', 'integer', Rule::exists('filieres', 'id')->where('is_active', true)],
            'niveau_id' => ['nullable', 'integer', Rule::exists('niveaux', 'id')->where('is_active', true)],
            'matiere_id' => ['nullable', 'integer', Rule::exists('matieres', 'id')->where('is_active', true)],
            'semester' => ['nullable', 'integer', Rule::in([1, 2])],
            'academic_year' => [
                'nullable',
                'regex:/^\d{4}-\d{4}$/',
                Rule::exists('epreuves', 'academic_year')->where('status', 'published'),
            ],
            'type' => ['nullable', Rule::in(['examen', 'rattrapage', 'devoir', 'controle_continu'])],
            'sort' => ['nullable', Rule::in(['recent', 'year', 'title', 'subject'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $keyword = $this->input('q');

        if (is_string($keyword)) {
            $this->merge(['q' => trim($keyword)]);
        }
    }
}
