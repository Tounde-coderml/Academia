<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class ReferentielRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('academic_referentials.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (is_string($code)) {
            $this->merge(['code' => trim($code)]);
        }
    }
}
