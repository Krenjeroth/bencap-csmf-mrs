<?php

namespace App\Http\Requests\Admin;

class ListOfficesRequest extends ListRequest
{
    protected function sortable(): array
    {
        return ['sort_order', 'code', 'name'];
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
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
