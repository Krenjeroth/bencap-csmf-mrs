<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StorePermissionRequest extends FormRequest
{
    /** resource.action: lower case, digits and dashes, e.g. service-types.view */
    public const TITLE_PATTERN = '/^[a-z][a-z0-9-]{0,49}\.[a-z][a-z0-9_-]{0,49}$/';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['title' => Str::lower(trim((string) $this->input('title')))]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100', 'regex:'.self::TITLE_PATTERN, 'unique:permissions,title'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.regex' => 'Use the form resource.action in lower case, for example reports.view.',
        ];
    }
}
