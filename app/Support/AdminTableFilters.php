<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTableFilters
{
    public static function fromRequest(Request $request, array $sorts, $defaultSort = 'date_desc', array $extraRules = [])
    {
        $data = $request->validate(array_merge([
            'search' => 'nullable|string|max:255',
            'sort' => ['nullable', 'string', Rule::in(array_keys($sorts))],
            'per_page' => ['nullable', 'integer', Rule::in(AdminPagination::PAGE_SIZES)],
            'page' => 'nullable|integer|min:1|max:1000000',
        ], $extraRules));

        return array_merge($data, [
            'search' => trim($data['search'] ?? ''),
            'sort' => $data['sort'] ?? $defaultSort,
            'per_page' => (int) ($data['per_page'] ?? AdminPagination::PER_PAGE),
        ]);
    }

    // Keep default URLs short, including links returning from a row action.
    public static function query(array $filters, $defaultSort = 'date_desc')
    {
        $page = $filters['page'] ?? null;
        unset($filters['page']);
        if ($page !== null) $filters['page'] = $page;

        return array_filter($filters, function ($value, $key) use ($defaultSort) {
            if ($key === 'sort' && $value === $defaultSort) return false;
            if ($key === 'per_page' && (int) $value === AdminPagination::PER_PAGE) return false;
            if ($key === 'page' && (int) $value <= 1) return false;

            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);
    }
}
