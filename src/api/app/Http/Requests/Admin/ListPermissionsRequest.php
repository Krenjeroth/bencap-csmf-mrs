<?php

namespace App\Http\Requests\Admin;

class ListPermissionsRequest extends ListRequest
{
    protected function sortable(): array
    {
        return ['title', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'title';
    }
}
