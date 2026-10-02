<?php

namespace App\Http\Requests\Admin;

class ListServicesRequest extends ListRequest
{
    protected function sortable(): array
    {
        return ['sort_order', 'name', 'charter_year'];
    }

    protected function defaultSort(): string
    {
        return 'sort_order';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'charter_year' => ['nullable', 'integer', 'between:2000,2100'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
