<?php

namespace App\Services\Admin;

use App\Merchandise\MerchandiseProduct;
use App\Shop\{eBook, eScore};
use App\Infograph\Infograph;
use Yajra\DataTables\EloquentDataTable;

/** Keep the existing moderation table usable for both named and titled products. */
class ReviewsTable
{
    const PRODUCTS = [eBook::class => ['e_books', 'title'], eScore::class => ['e_scores', 'title'],
        Infograph::class => ['infographs', 'title'], MerchandiseProduct::class => ['merchandise_products', 'name']];

    public function response($query)
    {
        request()->validate(['search.value' => 'nullable|string|max:255', 'start' => 'sometimes|integer|min:0',
            'length' => 'sometimes|integer|min:1|max:100', 'order' => 'sometimes|array|max:6',
            'order.*.column' => 'required|integer|between:0,5', 'order.*.dir' => 'required|in:asc,desc']);
        $cases = [];
        foreach (self::PRODUCTS as $model => [$table, $column]) {
            $cases[] = "WHEN reviews.reviewable_type = ? THEN (SELECT {$column} FROM {$table} WHERE {$table}.id = reviews.reviewable_id LIMIT 1)";
        }
        $query->select('reviews.*')->selectRaw('CASE '.implode(' ', $cases)." ELSE '' END AS reviewable_label", array_keys(self::PRODUCTS));
        $table = (new EloquentDataTable($query->with(['user', 'reviewable'])))
            ->whitelist(['created_at', 'reviewable.title'])
            ->filterColumn('reviewable.title', function ($query, $keyword) {
                $query->whereHasMorph('reviewable', array_keys(self::PRODUCTS), function ($query, $type) use ($keyword) {
                    $column = self::PRODUCTS[$type][1];
                    $query->whereRaw('LOWER('.$column.') LIKE ?', ['%'.mb_strtolower($keyword).'%']);
                });
            })
            ->order(function ($query) {
                foreach (request('order', []) as $order) {
                    $column = [0 => 'reviews.created_at', 1 => 'reviewable_label'][$order['column']] ?? null;
                    if ($column) $query->orderBy($column, $order['dir']);
                }
                $query->orderByDesc('reviews.id');
            })
            ->editColumn('created_at', fn ($review) => $review->created_at ? $review->created_at->toFormattedDateString() : null);
        foreach (['reviewable', 'user', 'rating', 'published', 'action'] as $field) {
            $view = $field === 'user' ? 'users' : ($field === 'action' ? 'actions' : $field);
            $table->addColumn($field, fn ($item) => view('admin.pages.reviews.table.'.$view, compact('item'))->render());
        }
        return $table->rawColumns(['reviewable', 'user', 'rating', 'published', 'action'])->make(true);
    }
}
