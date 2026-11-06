<?php

namespace App\Services\Admin;

use App\Services\Shop\DigitalMedia;
use App\Support\{AdminPagination, AdminTableFilters};
use Illuminate\Http\Request;

class DigitalProductsTable
{
    const SORTS = [
        'date_desc' => 'Date · newest', 'date_asc' => 'Date · oldest',
        'title_asc' => 'Title · A–Z', 'title_desc' => 'Title · Z–A',
        'purchases_desc' => 'Purchases · most', 'purchases_asc' => 'Purchases · fewest',
        'published_desc' => 'Publication · newest', 'published_asc' => 'Publication · oldest',
    ];

    public function filters(Request $request): array
    {
        return AdminTableFilters::fromRequest($request, self::SORTS, 'date_desc', [
            'status' => 'nullable|string|in:published,draft',
        ]) + ['status' => ''];
    }

    public function query(string $kind, array $filters)
    {
        abort_unless(isset(DigitalMedia::PRODUCTS[$kind]), 404);
        $model = DigitalMedia::PRODUCTS[$kind];
        $table = (new $model)->getTable();
        $query = $model::query();
        if ($filters['status'] === 'published') $query->whereNotNull('published_at');
        if ($filters['status'] === 'draft') $query->whereNull('published_at');
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($table, $term, $like) {
                foreach (['title', 'author', 'subtitle'] as $column) {
                    $query->orWhereRaw("LOWER({$table}.{$column}) LIKE ? ESCAPE '!'", [$like]);
                }
                if (ctype_digit($term)) $query->orWhere($table.'.id', $term);
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $column = ['date' => $table.'.created_at', 'title' => $table.'.title',
            'purchases' => 'purchases_count', 'published' => $table.'.published_at'][$sort];
        return $query->orderBy($column, $direction)->orderBy($table.'.id', $direction);
    }

    public function get(Request $request, string $kind): array
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters);
        $products = $this->query($kind, $filters)->paginate($filters['per_page'])
            ->appends(array_diff_key($returnFilters, ['page' => true]));
        $mainImages = app(DigitalMedia::class)->mainUrls($products->getCollection());

        // Load only the requested purchase list, rather than every buyer for every row.
        $selection = $request->validate(['purchases' => 'nullable|integer|min:1', 'purchases_page' => 'nullable|integer|min:1|max:1000000', 'topics' => 'nullable|string|in:1']);
        $purchaseProduct = null;
        $purchases = null;
        if (!empty($selection['purchases'])) {
            $model = DigitalMedia::PRODUCTS[$kind];
            $purchaseProduct = $model::findOrFail($selection['purchases']);
            $purchases = $purchaseProduct->purchases()->with('user:id,first_name,last_name')
                ->orderByDesc('created_at')->orderByDesc('id')->paginate(AdminPagination::PER_PAGE, ['*'], 'purchases_page')
                ->appends($returnFilters + ['purchases' => $purchaseProduct->id])->fragment('digital-purchases-modal');
        }
        $topics = app(DigitalTopics::class)->all($kind);
        return compact('kind', 'filters', 'returnFilters', 'products', 'mainImages', 'purchaseProduct', 'purchases', 'topics');
    }

    public function redirect(string $kind, array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($kind, $filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.'.$kind.'.index', AdminTableFilters::query($filters));
    }
}
