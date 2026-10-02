<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create (POST) or update (PUT) a service. A name is unique within its office
 * and charter year.
 */
class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $service = $this->route('service');
        $creating = ! $service instanceof Service;
        $required = $creating ? 'required' : 'sometimes';

        $officeId = (int) $this->input('office_id', $service?->office_id);
        $year = (int) $this->input('charter_year', $service?->charter_year ?? now()->year);

        return [
            'office_id' => [$required, 'integer', 'exists:offices,id'],
            'service_type_id' => [$required, 'integer', 'exists:service_types,id'],
            'name' => [$required, 'string', 'max:255',
                Rule::unique('services', 'name')
                    ->where('office_id', $officeId)
                    ->where('charter_year', $year)
                    ->ignore($service)],
            'charter_year' => ['sometimes', 'integer', 'between:2000,2100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'between:0,65535'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'This office already has a service with this name for that charter year.',
        ];
    }
}
