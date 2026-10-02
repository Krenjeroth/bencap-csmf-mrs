<?php

namespace App\Http\Requests\Admin;

use App\Models\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create (POST) or update (PUT) a service type. */
class ServiceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('type')) {
            $this->merge(['type' => trim((string) $this->input('type'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = $this->route('service_type');
        $required = $type instanceof ServiceType ? 'sometimes' : 'required';

        return [
            'type' => [$required, 'string', 'max:50', Rule::unique('service_types', 'type')->ignore($type)],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
