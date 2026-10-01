<?php

namespace App\Http\Requests\Admin;

class ListUsersRequest extends ListRequest
{
    protected function sortable(): array
    {
        return ['name', 'email', 'created_at', 'last_login_at'];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
