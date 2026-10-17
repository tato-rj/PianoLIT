<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\StripeMembershipForm;
use App\Billing\Plan;
use App\Billing\Sources\Apple;
use App\Billing\Stripe\{BillingException, MembershipBilling, MembershipManagement, SubscriptionState};
use App\Events\Memberships\NewTrial;
use App\Mail\Memberships\MembershipConfirmed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MembershipsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web')->except('pricing');
    }

    public function pricing()
    {
        $plans = Plan::all()->reverse();
        $selected = request()->query('checkout');
        $checkoutPlan = auth('web')->check() && is_string($selected) ? $plans->firstWhere('name', $selected) : null;
        $checkoutUrl = $checkoutPlan ? route('webapp.membership.checkout', $checkoutPlan) : null;
        return view('webapp.membership.pricing.index', compact('plans', 'checkoutUrl'));
    }

    public function edit(MembershipBilling $billing)
    {
        $user = auth('web')->user();
        if (!$user->membership) return redirect()->route('webapp.membership.pricing');
        if ($user->hasMembershipWith(Apple::class)) return view('webapp.membership.edit.apple');
        try {
            $source = $billing->source($user);
            $stripeKey = $billing->publicKey($user);
        } catch (\Throwable $exception) { return $this->failure(request(), $exception, 'webapp.discover'); }
        $plans = Plan::all()->reverse();
        $cardOperation = (string) Str::uuid();
        $overview = \App\Support\MembershipOverview::forSource($source);
        $savedCard = $billing->savedCard($user);
        $selected = request()->query('checkout');
        $checkoutPlan = $overview['ended'] && is_string($selected) ? $plans->firstWhere('name', $selected) : null;
        $checkoutUrl = $checkoutPlan ? route('webapp.membership.checkout', $checkoutPlan) : null;
        return view('webapp.membership.edit.index', compact('plans', 'source', 'stripeKey', 'cardOperation', 'overview', 'checkoutUrl', 'savedCard'));
    }

    public function checkout(Plan $plan, Request $request, MembershipBilling $billing)
    {
        try {
            $stripeKey = $billing->publicKey($request->user('web'));
        } catch (\Throwable $exception) { return $this->failure($request, $exception, 'webapp.membership.edit'); }
        $active = $billing->activePurchase($request->user('web'));
        if ($active && $active->subscription_id && $active->plan !== $plan->name) {
            return redirect()->route('webapp.membership.checkout', $active->plan)->with('error', 'Finish your existing checkout before choosing a different plan.');
        }
        $operationId = $active ? $active->id : (string) Str::uuid();
        $activeCoupon = $active ? $active->coupon : null;
        $trialDays = $billing->trialDays($request->user('web'), $plan);
        \App\Activity\Actions::record($request, 'checkout_started', [], 'plan', $plan->id);
        $savedCard = $billing->savedCard($request->user('web'));
        $data = compact('plan', 'stripeKey', 'operationId', 'trialDays', 'activeCoupon', 'savedCard');
        if ($request->expectsJson()) {
            return response()->json(['html' => view('webapp.membership.checkout.modal', $data)->render()])
                ->header('Cache-Control', 'private, no-store');
        }
        return view('webapp.membership.checkout.index', $data);
    }

    public function prepare(Request $request, Plan $plan, MembershipBilling $billing)
    {
        $data = $request->validate(['operation_id' => 'required|uuid', 'coupon' => 'nullable|string|max:80',
            'use_saved_card' => 'sometimes|boolean', 'saved_card_reference' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/D']]);
        try { return response()->json($billing->prepare($request->user('web'), $data['operation_id'], 'purchase', $plan, $data['coupon'] ?? null, $request->boolean('use_saved_card'), $data['saved_card_reference'] ?? null)); }
        catch (\Throwable $exception) { return $this->failure($request, $exception); }
    }

    public function prepareCard(Request $request, MembershipBilling $billing)
    {
        $data = $request->validate(['operation_id' => 'required|uuid']);
        try { return response()->json($billing->prepare($request->user('web'), $data['operation_id'], 'card')); }
        catch (\Throwable $exception) { return $this->failure($request, $exception); }
    }

    public function purchase(StripeMembershipForm $request, Plan $plan, MembershipBilling $billing)
    {
        try { return response()->json($billing->purchase($request->user('web'), $plan, $request->operation_id, $request->setup_intent, $request->boolean('use_saved_card'), $request->input('saved_card_reference'))); }
        catch (\Throwable $exception) { return $this->failure($request, $exception); }
    }

    public function complete(Request $request, MembershipBilling $billing)
    {
        $data = $request->validate(['operation_id' => 'required|uuid']);
        try { $result = $billing->finish($request->user('web'), $data['operation_id']); }
        catch (\Throwable $exception) { return $this->failure($request, $exception); }
        $subscription = json_decode(json_encode($result['subscription']));
        $user = $request->user('web');
        $previous = $request->session()->get('membership-confirmation', []);
        $emailQueued = ($previous['operation_id'] ?? null) === $data['operation_id'] && !empty($previous['email_queued']);
        if ($result['first_completion']) {
            // The durable completion flag prevents duplicate confirmations on checkout retries.
            // Email failures must never turn an already successful payment into a checkout error.
            try {
                if (app(\App\Mail\AutomaticEmailSettings::class)->enabled('membership-confirmation')) {
                    $source = $billing->source($user);
                    Mail::to($user->email)->queue(new MembershipConfirmed(
                        $user->first_name ?: 'there', $source->plan, $source->status === 'trialing',
                        $source->renews_at ? $source->renews_at->toFormattedDateString() : null
                    ));
                    $emailQueued = true;
                }
            } catch (\Throwable $exception) { report($exception); }
            try {
                \App\Activity\Actions::record($request, $subscription->status === 'trialing' ? 'trial_started' : 'subscription_activated', ['status' => $subscription->status]);
                (new \App\Services\CampaignAttribution)->subscribed($request, $request->user('web'), (object) $result['customer'], $subscription);
                if ($subscription->status === 'trialing') event(new NewTrial($request->user('web')));
            } catch (\Throwable $exception) { report($exception); }
        }
        $request->session()->flash('membership-confirmation', [
            'user_id' => $user->id, 'operation_id' => $data['operation_id'], 'email_queued' => $emailQueued,
            // Use this verified subscription's dates, including when an old member subscribes again.
            'subscription_id' => $subscription->id, 'purchased_at' => $subscription->created ?? now()->timestamp,
            'trial_ends_at' => $subscription->trial_end ?? null,
        ]);
        return response()->json(['redirect' => route('webapp.discover')]);
    }

    public function success()
    {
        // Preserve old return links without keeping a separate welcome screen.
        session()->keep('membership-confirmation');
        return redirect()->route('webapp.discover');
    }

    public function updatePlan(Request $request, MembershipManagement $management)
    {
        $data = $request->validate(['operation_id' => 'required|uuid', 'plan' => 'required|string|exists:plans,name']);
        try { $management->change($request->user('web'), $data['operation_id'], 'plan', Plan::byName($data['plan'])); }
        catch (\Throwable $exception) { return $this->failure($request, $exception); }
        $pending = optional(optional($request->user('web')->fresh()->membership)->source)->pending_plan;
        return back()->with('status', $pending ? 'Your plan change is scheduled for the end of your current billing period.' : 'Your current plan will continue. No plan change is scheduled.');
    }

    public function updateCard(StripeMembershipForm $request, MembershipBilling $billing)
    {
        try { $billing->updateCard($request->user('web'), $request->operation_id, $request->setup_intent); }
        catch (\Throwable $exception) { return $this->failure($request, $exception); }
        $request->session()->flash('status', 'Your card has been successfully updated.');
        return response()->json(['redirect' => route('webapp.membership.edit').'#update-card-tab']);
    }

    public function updateBillingStatus(Request $request, MembershipManagement $management)
    {
        $data = $request->validate(['operation_id' => 'required|uuid', 'paused' => 'required|boolean']);
        return $this->change($request, $management, $data['paused'] ? 'pause' : 'unpause', $data['paused'] ? 'Your billing is paused.' : 'Your billing has resumed.');
    }

    public function removeCard(Request $request, \App\Billing\Stripe\CardRemoval $removal)
    {
        $data = $request->validate(['operation_id' => 'required|uuid', 'saved_card_reference' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D']]);
        try { $removal->remove($request->user('web'), $data['operation_id'], $data['saved_card_reference']); }
        catch (\Throwable $exception) {
            $response = $this->failure($request, $exception, 'webapp.membership.edit');
            return $request->expectsJson() ? $response : $response->setTargetUrl(route('webapp.membership.edit').'#update-card-tab');
        }
        return redirect()->to(route('webapp.membership.edit').'#update-card-tab')->with('status', 'Your saved card has been removed.');
    }

    public function cancel(Request $request, MembershipManagement $management)
    {
        return $this->change($request, $management, 'cancel', 'Your membership will end at the end of the current billing period.');
    }

    public function resume(Request $request, MembershipManagement $management)
    {
        return $this->change($request, $management, 'resume', 'Your membership cancellation has been withdrawn.');
    }

    private function change(Request $request, MembershipManagement $management, string $action, string $message)
    {
        $data = $request->validate(['operation_id' => 'required|uuid']);
        try { $management->change($request->user('web'), $data['operation_id'], $action); }
        catch (\Throwable $exception) { return $this->failure($request, $exception); }
        if ($action === 'cancel') \App\Activity\Actions::record($request, 'subscription_canceled');
        if ($action === 'cancel' && optional(optional($request->user('web')->fresh()->membership)->source)->isEnded()) $message = 'Your checkout has been canceled.';
        if (in_array($action, ['cancel', 'resume', 'unpause'], true)) {
            return redirect()->to(route('webapp.membership.edit').'#update-card-tab')->with('status', $message);
        }
        return back()->with('status', $message);
    }

    public function invoices(Request $request, MembershipManagement $management)
    {
        $data = $request->validate(['after' => ['nullable', 'string', 'max:255', 'regex:/^in_[A-Za-z0-9]+$/D']]);
        try {
            $result = $management->invoices($request->user('web'), $data['after'] ?? null);
            $last = count($result['invoices']) ? end($result['invoices'])['id'] : null;
            return response()->json(['html' => view('webapp.membership.edit.sections.invoices.results', $result)->render(),
                'after' => $result['has_more'] ? $last : null]);
        } catch (\Throwable $exception) { return $this->failure($request, $exception); }
    }

    public function coupon(Request $request, Plan $plan, MembershipBilling $billing)
    {
        $data = $request->validate(['coupon' => 'required|string|max:80']);
        try {
            $account = $billing->account($request->user('web'));
            $price = $billing->price($plan, $account);
            $coupon = $billing->coupon($account, $data['coupon']);
            $product = SubscriptionState::id($price['product'] ?? null);
            $products = data_get($coupon, 'applies_to.products', []);
            if (($products && !in_array($product, $products, true)) || (!empty($coupon['amount_off']) && ($coupon['currency'] ?? null) !== 'usd')) {
                throw new BillingException('This coupon does not apply to this plan.');
            }
            $amount = isset($coupon['percent_off']) ? (int) round($plan->price * (100 - $coupon['percent_off']) / 100) : max(0, $plan->price - ($coupon['amount_off'] ?? 0));
            return response()->json(['isValid' => true, 'amount' => $amount,
                'message' => 'Coupon applied: $'.number_format($amount / 100, 2).' for the discounted billing period. Later renewals follow the coupon terms.']);
        } catch (\Throwable $exception) { return $this->failure($request, $exception); }
    }

    private function failure(Request $request, \Throwable $exception, ?string $route = null)
    {
        if ($exception instanceof BillingException) $message = $exception->getMessage();
        elseif ($exception instanceof \Stripe\Exception\CardException) $message = 'Your card could not be accepted. Please check the details or use a different card.';
        else {
            report($exception);
            $message = 'We could not complete this billing request. Please try again. If it continues, contact contact@pianolit.com.';
        }
        if ($request->expectsJson()) return response()->json(['message' => $message], 422);
        return ($route ? redirect()->route($route) : back())->with('error', e($message));
    }
}
