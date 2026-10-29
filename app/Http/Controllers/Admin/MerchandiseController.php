<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Merchandise\MerchandiseProduct;
use App\Services\Admin\{MerchandiseCatalog, PrintfulCatalogSync};
use App\Services\Shop\{MerchandiseImages, Printful, PrintfulException, PrintfulProductNotFound};
use App\Support\{AdminPagination, AdminTableFilters};
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MerchandiseController extends Controller
{
    const SORTS = ['date_desc' => 'Recently imported', 'name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A'];

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth('admin')->check() && auth('admin')->user()->isManager(), 403);
            return $next($request);
        });
    }

    public function index(Request $request, PrintfulCatalogSync $sync)
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS);
        $syncNotice = null;
        try {
            $sync->reconcile();
        } catch (PrintfulException $exception) {
            $syncNotice = 'Printful could not be checked right now. Your existing catalog is shown; automatic checks will retry.';
        }
        $query = app(\App\Services\Shop\Storefront::class)->query()->with('mainImage')->withCount('images');
        if ($filters['search'] !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($filters['search'])).'%';
            $query->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$like]);
        }
        if ($filters['sort'] === 'date_desc') $query->orderByDesc('synced_at');
        else $query->orderBy('name', $filters['sort'] === 'name_asc' ? 'asc' : 'desc');
        $lastPage = max(1, (int) ceil((clone $query)->count() / $filters['per_page']));
        $page = min((int) ($filters['page'] ?? 1), $lastPage);
        $filters['page'] = $page;
        $products = $query->orderBy('id')->paginate($filters['per_page'], ['*'], 'page', $page)->appends(AdminTableFilters::query($filters));
        $returnFilters = AdminTableFilters::query($filters);
        return view('admin.pages.merchandise.index', compact('products', 'filters', 'returnFilters', 'syncNotice'));
    }

    public function discover(Request $request, Printful $printful)
    {
        $request->validate(['page' => 'nullable|integer|min:1|max:100000']);
        $page = (int) $request->input('page', 1);
        $limit = AdminPagination::PER_PAGE;
        $offset = ($page - 1) * $limit;
        $error = null;
        $rows = [];
        $total = 0;
        $imported = collect();
        try {
            $response = $printful->products($offset, $limit);
            if (isset($response['paging']['offset']) && (int) $response['paging']['offset'] !== $offset) {
                throw new PrintfulException('Printful could not load this catalog page. Return to the first page and try again.');
            }
            foreach ($response['result'] as $row) {
                if (!is_array($row) || !is_numeric($row['id'] ?? null) || (int) $row['id'] < 1 || !is_string($row['name'] ?? null)) continue;
                $rows[] = ['id' => (int) $row['id'], 'name' => $row['name'], 'variants' => (int) ($row['variants'] ?? 0)];
            }
            $total = max(count($rows) + $offset, (int) ($response['paging']['total'] ?? 0));
            $imported = MerchandiseProduct::where('printful_store_id', $printful->storeId())->whereIn('printful_product_id', array_column($rows, 'id'))->get()->keyBy('printful_product_id');
        } catch (PrintfulException $exception) {
            $error = $exception->getMessage();
        }
        $products = new LengthAwarePaginator(array_slice($rows, 0, $limit), $total, $limit, $page, ['path' => route('admin.merchandise.discover')]);
        return view('admin.pages.merchandise.import', compact('products', 'imported', 'error'));
    }

    public function import(Request $request, MerchandiseCatalog $catalog)
    {
        $data = $request->validateWithBag('merchandise', ['printful_product_id' => 'required|integer|min:1|max:'.PHP_INT_MAX]);
        try {
            [$product, $warnings, $colorWarnings] = $catalog->import((int) $data['printful_product_id']);
        } catch (PrintfulException $exception) {
            return redirect()->route('admin.merchandise.discover')->with('error', $exception->getMessage());
        }
        return $this->importResult($product, $warnings, $colorWarnings);
    }

    public function edit(Request $request, MerchandiseProduct $merchandise)
    {
        $request->validate(['variants_page' => 'nullable|integer|min:1|max:100000']);
        $returnFilters = $this->returnFilters();
        $product = $merchandise->load('images');
        $removed = $product->images()->onlyTrashed()->get();
        $page = (int) $request->input('variants_page', 1);
        $all = collect($product->variants);
        $variants = new LengthAwarePaginator($all->forPage($page, AdminPagination::PER_PAGE)->values(), $all->count(), AdminPagination::PER_PAGE, $page,
            ['path' => route('admin.merchandise.edit', $product), 'pageName' => 'variants_page', 'fragment' => 'variants']);
        $variants->appends($returnFilters);
        return view('admin.pages.merchandise.edit', compact('product', 'removed', 'variants', 'returnFilters'));
    }

    public function refresh(Request $request, MerchandiseProduct $merchandise, MerchandiseCatalog $catalog)
    {
        $revision = $this->revision($request);
        try {
            [$product, $warnings, $colorWarnings] = $catalog->import((int) $merchandise->printful_product_id, $merchandise, $revision);
        } catch (PrintfulProductNotFound $exception) {
            try {
                app(PrintfulCatalogSync::class)->reconcile();
                if (!MerchandiseProduct::whereKey($merchandise->id)->exists()) {
                    return redirect()->route('admin.merchandise.index', $this->returnFilters())
                        ->with('status', 'This product was deleted in Printful and has been removed from your catalog.');
                }
            } catch (PrintfulException $syncException) {
                // A failed verification must not remove the product.
            }
            return $this->backToImages($merchandise)->with('error', 'The product could not be verified in Printful. Your local images have been kept. Try again later.');
        } catch (PrintfulException $exception) {
            return $this->backToImages($merchandise)->with('error', $exception->getMessage());
        }
        return $this->importResult($product, $warnings, $colorWarnings);
    }

    public function upload(Request $request, MerchandiseProduct $merchandise, MerchandiseCatalog $catalog)
    {
        $data = $request->validateWithBag('merchandise', [
            'revision' => 'required|integer|min:1', 'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=6000,max_height=6000',
            'alt' => 'nullable|string|max:255',
        ]);
        try {
            $catalog->upload($merchandise, (int) $data['revision'], $request->file('images'), $data['alt'] ?? null);
        } catch (PrintfulException $exception) {
            return $this->backToImages($merchandise)->with('error', $exception->getMessage());
        }
        return $this->backToImages($merchandise)->with('status', 'Your images have been added to the gallery.');
    }

    public function main(Request $request, MerchandiseProduct $merchandise, int $image, MerchandiseCatalog $catalog)
    {
        $catalog->change($merchandise, $this->revision($request), function ($product) use ($image, $catalog) {
            $product->main_image_id = $catalog->image($product, $image)->id;
        });
        return $this->backToImages($merchandise)->with('status', 'The main product image has been updated.');
    }

    public function updateImage(Request $request, MerchandiseProduct $merchandise, int $image, MerchandiseCatalog $catalog)
    {
        $data = $request->validateWithBag('merchandise', ['revision' => 'required|integer|min:1', 'alt' => 'required|string|max:255']);
        $catalog->change($merchandise, (int) $data['revision'], function ($product) use ($image, $catalog, $data) {
            $catalog->image($product, $image)->update(['alt' => $data['alt']]);
        });
        return $this->backToImages($merchandise)->with('status', 'The image description has been updated.');
    }

    public function move(Request $request, MerchandiseProduct $merchandise, int $image, MerchandiseCatalog $catalog)
    {
        $data = $request->validateWithBag('merchandise', ['revision' => 'required|integer|min:1', 'direction' => 'required|in:up,down']);
        $catalog->change($merchandise, (int) $data['revision'], function ($product) use ($image, $catalog, $data) {
            $selected = $catalog->image($product, $image);
            $ordered = $product->images()->get();
            $index = $ordered->search(fn ($item) => $item->id === $selected->id);
            $next = $index + ($data['direction'] === 'up' ? -1 : 1);
            if ($next < 0 || $next >= $ordered->count()) return;
            $ids = $ordered->modelKeys();
            [$ids[$index], $ids[$next]] = [$ids[$next], $ids[$index]];
            foreach ($ids as $position => $id) $product->images()->whereKey($id)->update(['position' => $position + 1]);
        });
        return $this->backToImages($merchandise)->with('status', 'The gallery order has been updated.');
    }

    public function remove(Request $request, MerchandiseProduct $merchandise, int $image, MerchandiseCatalog $catalog)
    {
        $catalog->change($merchandise, $this->revision($request), function ($product) use ($image, $catalog) {
            $catalog->image($product, $image)->delete();
            if ((int) $product->main_image_id === $image) $product->main_image_id = $product->images()->value('id');
        });
        return $this->backToImages($merchandise)->with('status', 'The image has been removed from the gallery. You can restore it below.');
    }

    public function restore(Request $request, MerchandiseProduct $merchandise, int $image, MerchandiseCatalog $catalog)
    {
        try {
            $catalog->change($merchandise, $this->revision($request), function ($product) use ($image, $catalog) {
                if ($product->images()->count() >= MerchandiseImages::MAX_IMAGES) throw new PrintfulException('A product can have up to 40 gallery images.');
                $restored = $catalog->image($product, $image, true);
                $restored->position = (int) $product->images()->withTrashed()->max('position') + 1;
                $restored->restore();
                if (!$product->main_image_id) $product->main_image_id = $restored->id;
            });
        } catch (PrintfulException $exception) {
            return $this->backToImages($merchandise)->with('error', $exception->getMessage());
        }
        return $this->backToImages($merchandise)->with('status', 'The image has been restored to the gallery.');
    }

    private function revision(Request $request): int
    {
        return (int) $request->validateWithBag('merchandise', ['revision' => 'required|integer|min:1'])['revision'];
    }

    private function backToImages(MerchandiseProduct $product)
    {
        return redirect()->route('admin.merchandise.edit', ['merchandise' => $product] + $this->returnFilters())->withFragment('images');
    }

    private function returnFilters(): array
    {
        $query = Request::create('/', 'GET', request()->query());
        return AdminTableFilters::query(AdminTableFilters::fromRequest($query, self::SORTS));
    }

    private function importResult(MerchandiseProduct $product, int $warnings, int $colorWarnings)
    {
        $response = $this->backToImages($product)->with('status', 'Product saved. Your gallery choices have been kept.');
        $notices = [];
        if ($warnings) $notices[] = $warnings.' mockup(s) could not be added. Refresh to retry, or upload a downloaded copy.';
        if ($colorWarnings) $notices[] = 'Some color swatches could not be refreshed. Saved colors were kept where the variant still matches; otherwise the option stays neutral. Refresh from Printful to retry.';
        if ($notices) $response->with('merchandise_notice', implode(' ', $notices));
        return $response;
    }
}
