<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Shop\{LocalShipmentSimulator, OrderEmails};
use App\Shop\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\Mail\Markdown;
use Illuminate\Validation\Rule;

class LocalShopShipmentsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(app()->environment('local'), 404);
            abort_unless(auth('admin')->check() && auth('admin')->user()->isManager(), 403);
            return $next($request);
        });
    }

    public function create(LocalShipmentSimulator $simulator)
    {
        $order = $simulator->create();
        return redirect()->route('admin.shop-orders.show', $order->id)->with('status', 'Sample order created. Its confirmation email is available as a local preview.');
    }

    public function simulate(Request $request, string $order, LocalShipmentSimulator $simulator)
    {
        $order = ShopOrder::findOrFail($order);
        abort_unless($simulator->eligible($order), 404);
        $data = $request->validate(['event' => ['required', Rule::in(['shipment_sent', 'shipment_delivered'])]]);
        $changed = $simulator->simulate($order, $data['event']);
        return redirect()->route('admin.shop-orders.show', $order->id)->with('status', $changed
            ? 'Local shipment updated. Tracking and email previews are ready.' : 'This event was already processed. No duplicate email or shipment was created.');
    }

    public function preview(string $order, int $email, string $format, LocalShipmentSimulator $simulator)
    {
        $order = ShopOrder::findOrFail($order);
        abort_unless($simulator->eligible($order), 404);
        $email = $order->emails()->whereKey($email)->where('status', 'preview')->firstOrFail();
        abort_unless(isset(OrderEmails::TYPES[$email->kind]), 404);
        $mail = app(OrderEmails::class)->message($email);
        $mail->build();
        $data = $mail->buildViewData();
        $markdown = clone app(Markdown::class);
        $markdown->theme($mail->theme ?: config('mail.markdown.theme', 'default'));
        $website = config('app.url');
        config(['app.url' => \App\Support\ProfileUrl::base()]);
        try { $body = $format === 'text' ? $markdown->renderText($mail->markdown, $data) : $markdown->render($mail->markdown, $data); }
        finally { config(['app.url' => $website]); }
        return response((string) $body)->header('Content-Type', $format === 'text' ? 'text/plain; charset=UTF-8' : 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Content-Security-Policy', "sandbox allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation; default-src 'none'; style-src 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src https: http: data:; frame-ancestors 'self'; form-action 'none'");
    }
}
