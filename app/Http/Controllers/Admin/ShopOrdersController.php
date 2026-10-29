<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Shop\ShopOrder;
use App\Services\Shop\{MerchandiseFulfillment, ShopCheckout};
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class ShopOrdersController extends Controller
{
    const SORTS = ['date_desc' => 'Newest first', 'date_asc' => 'Oldest first'];

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth('admin')->check() && auth('admin')->user()->isManager(), 403);
            return $next($request);
        });
    }

    private function query()
    {
        return ShopOrder::query()->when(app()->environment('local'), fn ($query) => $query->whereIn('mode', ['demo', 'sandbox']), fn ($query) => $query->where('mode', 'live'));
    }

    public function index(Request $request)
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS);
        $query = $this->query();
        if ($filters['search'] !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']).'%';
            $query->whereRaw("id LIKE ? ESCAPE '!'", [$like]);
        }
        $orders = $query->orderBy('created_at', $filters['sort'] === 'date_asc' ? 'asc' : 'desc')->orderBy('id')
            ->paginate($filters['per_page'])->appends(AdminTableFilters::query($filters));
        return response()->view('admin.pages.shop-orders.index', compact('orders', 'filters'))->header('Cache-Control', 'private, no-store');
    }

    public function show(string $order)
    {
        $order = $this->query()->with(['shipments', 'emails'])->findOrFail($order);
        $canSimulate = app(\App\Services\Shop\LocalShipmentSimulator::class)->eligible($order);
        return response()->view('admin.pages.shop-orders.show', compact('order', 'canSimulate'))->header('Cache-Control', 'private, no-store');
    }

    public function retry(string $order)
    {
        $order = $this->query()->findOrFail($order);
        abort_unless($order->kind === 'merchandise' && $order->paid_at && $order->status === 'paid', 422);
        try { app(ShopCheckout::class)->paid($order->id); app(MerchandiseFulfillment::class)->process($order->id); }
        catch (\Throwable $exception) { return back()->with('error', 'The order could not be confirmed. Review its payment and Printful status before retrying.'); }
        return back()->with('status', $order->fresh()->submitted_at ? 'The order has been sent to Printful.' : 'The order is still awaiting fulfillment. Please review its status.');
    }

    public function retryEmail(string $order, int $email)
    {
        $order = $this->query()->findOrFail($order);
        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $email) {
            $email = $order->emails()->whereKey($email)->lockForUpdate()->firstOrFail();
            abort_unless($order->paid_at && $email->status === 'uncertain', 422);
            $email->update(['status' => 'pending', 'claimed_at' => null, 'last_error' => null]);
        }, 3);
        return back()->with('status', 'The email is queued for another delivery attempt.');
    }
}
