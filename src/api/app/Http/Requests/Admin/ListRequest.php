<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared list parameters: ?q=&sort=&per_page=&page=.
 * Subclasses declare which columns may be sorted; anything else is rejected,
 * so user input never reaches ORDER BY unchecked.
 */
abstract class ListRequest extends FormRequest
{
    /** @return list<string> */
    abstract protected function sortable(): array;

    abstract protected function defaultSort(): string;

    public function authorize(): bool
    {
        return true; // Route middleware checks the permission.
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $sorts = collect($this->sortable())->flatMap(fn (string $c) => [$c, "-{$c}"])->all();

        return [
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in($sorts)],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array{0: string, 1: 'asc'|'desc'} */
    public function sortColumn(): array
    {
        $sort = (string) ($this->validated('sort') ?? $this->defaultSort());

        return str_starts_with($sort, '-') ? [substr($sort, 1), 'desc'] : [$sort, 'asc'];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 15);
    }

    /** The search term with LIKE wildcards escaped. */
    public function search(): ?string
    {
        $q = trim((string) $this->validated('q'));

        return $q === '' ? null : '%'.addcslashes($q, '%_\\').'%';
    }
}
