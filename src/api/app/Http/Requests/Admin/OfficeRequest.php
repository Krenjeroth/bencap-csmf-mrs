<?php

namespace App\Http\Requests\Admin;

use App\Models\Office;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Create (POST) or update (PUT) an office. The slug is the guest form's web
 * address (/f/{slug}); it is generated from the code when left empty.
 * parent_id places the office under a top-level office (one level only).
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
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:offices,id', $this->oneLevelParent($office)],
        ];
    }

    /**
     * The hierarchy is one level deep: the parent must be a top-level office
     * other than this one, and an office with offices under it stays on top.
     */
    private function oneLevelParent(?Office $office): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($office): void {
            if ($value === null) {
                return;
            }
            if ($office !== null && (int) $value === $office->id) {
                $fail('An office cannot sit under itself.');

                return;
            }
            $parent = Office::find($value);
            if ($parent?->parent_id !== null) {
                $fail("{$parent->code} already sits under another office. Choose a top-level office.");

                return;
            }
            if ($office !== null && $office->children()->exists()) {
                $fail("Other offices sit under {$office->code}, so it must stay a top-level office.");
            }
        };
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
        return ['slug' => 'guest form address', 'parent_id' => 'parent office'];
    }
}
