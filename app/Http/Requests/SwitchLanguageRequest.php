<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class SwitchLanguageRequest extends FormRequest
{
    /**
     * Authorize all users to switch language.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for switching language.
     */
    public function rules(): array
    {
        return [
            'language' => ['required_without:locale', 'string', 'exists:languages,code'],
            'locale' => ['nullable', 'string', 'exists:languages,code'],
        ];
    }
}
