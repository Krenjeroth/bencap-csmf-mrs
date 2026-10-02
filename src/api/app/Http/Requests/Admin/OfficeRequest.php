<?php

namespace App\Http\Requests\Admin;

use App\Models\Office;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Create (POST) or update (PUT) an office. The slug is the guest form's web
 * address (/f/{slug}); it is generated from the code when left empty.
 */
class OfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        foreach (['code', 'name'] as $field) {
            if ($this->has($field)) {
                $data[$field] = trim((string) $this->input($field));
            }
        }
        if ($this->has('slug') || $this->isMethod('post')) {
            $slug = trim((string) $this->input('slug'));
            $data['slug'] = $slug !== '' ? Str::slug($slug) : Str::slug((string) ($data['code'] ?? $this->input('code')));
        }
        $this->merge($data);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $office = $this->route('office');
        $creating = ! $office instanceof Office;
        $required = $creating ? 'required' : 'sometimes';

        return [
            'code' => [$required, 'string', 'max:30', Rule::unique('offices', 'code')->ignore($office)],
            'name' => [$required, 'string', 'max:150'],
            'slug' => [$required, 'string', 'max:60', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('offices', 'slug')->ignore($office)],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'between:0,65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lower-case letters, numbers and dashes only, for example og-library.',
            // The slug may have been made from the code, so name the address, not the field.
            'slug.unique' => 'Another office already uses the guest form address /f/:input.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['slug' => 'guest form address'];
    }
}
