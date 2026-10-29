<?php

namespace Tests\Review;

use App\Billing\Membership;
use App\Billing\Sources\Stripe;
use App\Merchandise\Purchase;
use App\Services\Navigation\AccountMenu;
use App\Shop\{eBook, ShopOrder};
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountNavigationTest extends ReviewTestCase
{
    private function account()
    {
        return Model::withoutEvents(fn () => create(User::class));
    }

    private function shortcuts($user, $prefix = '')
    {
        $this->app->instance('request', Request::create('http://'.$prefix.'localhost'));
        return app(AccountMenu::class)->shortcuts($user);
    }

    private function order($user, array $changes = [])
    {
        $id = (string) Str::uuid();
        return ShopOrder::create($changes + ['id' => $id, 'user_id' => $user->id, 'kind' => 'merchandise', 'mode' => 'live',
            'owner_hash' => hash('sha256', $id), 'printful_store_id' => 12345, 'printful_external_id' => substr(hash('sha256', $id), 0, 32),
            'bag_hash' => hash('sha256', 'bag'), 'items' => [], 'recipient' => ['email' => $user->email], 'currency' => 'usd',
            'subtotal' => 100, 'shipping' => 0, 'tax' => 0, 'total' => 100, 'fulfillment_cost' => 50,
            'shipping_id' => 'STANDARD', 'shipping_name' => 'Standard', 'expires_at' => now()->addHour(), 'paid_at' => now()]);
    }

    public function test_unused_shortcuts_only_appear_in_their_own_hub()
    {
        $user = $this->account();
        foreach (['', 'my.', 'learn.', 'shop.', 'blog.', 'games.', 'metronome.'] as $prefix) {
            $links = $this->shortcuts($user, $prefix);
            $this->assertSame($prefix === 'my.', $links['membership']);
            $this->assertSame($prefix === 'learn.', $links['downloads']);
            $this->assertSame($prefix === 'shop.', $links['orders']);
            $this->assertSame('http://my.localhost/membership/pricing', $links['membershipUrl']);
        }
        $guest = $this->shortcuts(null, 'my.');
        $this->assertTrue($guest['membership']);
        $this->assertFalse($guest['downloads']);
        $this->assertFalse($guest['orders']);
    }

    public function test_account_owned_history_makes_shortcuts_available_in_every_hub_without_provider_calls()
    {
        $user = $this->account();
        // An expired/retired source need not be loaded to keep the management link.
        Model::withoutEvents(fn () => Membership::create(['user_id' => $user->id, 'source_type' => Stripe::class, 'source_id' => 999999]));
        Model::withoutEvents(fn () => Purchase::create(['user_id' => $user->id, 'item_type' => eBook::class, 'item_id' => 999999]));
        $this->order($user);
        foreach (['', 'my.', 'learn.', 'shop.', 'blog.', 'games.', 'metronome.'] as $prefix) {
            $links = $this->shortcuts($user, $prefix);
            foreach (['membership', 'downloads', 'orders'] as $key) $this->assertTrue($links[$key]);
            $this->assertSame('http://my.localhost/membership/edit', $links['membershipUrl']);
        }
        $other = $this->account();
        $other->email = $user->email;
        $links = $this->shortcuts($other);
        foreach (['membership', 'downloads', 'orders'] as $key) $this->assertFalse($links[$key]);
        $this->assertFalse($user->relationLoaded('membership'), 'Navigation must not expose new loaded relationships in shared JSON');
    }

    public function test_only_paid_visible_merchandise_counts_and_super_users_keep_a_working_destination()
    {
        $user = $this->account();
        $this->order($user, ['paid_at' => null]);
        $this->order($user, ['kind' => 'digital']);
        $this->order($user, ['mode' => 'demo']);
        $this->assertFalse($this->shortcuts($user)['orders']);
        $user->super_user = true;
        $links = $this->shortcuts($user);
        $this->assertTrue($links['membership']);
        $this->assertSame('http://my.localhost/settings', $links['membershipUrl']);
        $this->app->instance('env', 'local');
        $this->assertTrue($this->shortcuts($user)['orders']);
        $this->app->instance('env', 'testing');
    }

    public function test_account_pages_do_not_also_highlight_their_hub_and_home_only_marks_the_homepage()
    {
        $user = $this->account();
        Model::withoutEvents(fn () => Membership::create(['user_id' => $user->id, 'source_type' => Stripe::class, 'source_id' => 999999]));
        Model::withoutEvents(fn () => Purchase::create(['user_id' => $user->id, 'item_type' => eBook::class, 'item_id' => 999999]));
        $this->order($user);
        foreach ([
            ['localhost', 'users.profile', 'My profile'],
            ['localhost', 'webapp.users.profile', 'My profile'],
            ['localhost', 'users.purchases', 'My downloads'],
            ['localhost', 'users.orders', 'Track your orders'],
            ['localhost', 'contact', 'Help & Support'],
            ['learn.localhost', 'contact', 'Help & Support'],
            ['my.localhost', 'webapp.membership.edit', 'My membership'],
            ['localhost', 'home', 'Home'],
            ['localhost', 'privacy', null],
            ['my.localhost', 'webapp.discover', 'Web app'],
            ['learn.localhost', 'crashcourses.show', 'Learn'],
            ['shop.localhost', 'ebooks.show', 'Shop'],
            ['blog.localhost', 'posts.show', 'Blog'],
        ] as [$host, $routeName, $expected]) {
            $request = Request::create('http://'.$host.'/');
            $route = (new \Illuminate\Routing\Route('GET', '/', fn () => null))->name($routeName);
            $request->setRouteResolver(fn () => $route);
            $request->setLaravelSession(app('session')->driver());
            $this->app->instance('request', $request);
            $html = view('components.account.menu', ['account' => $user])->render();
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $current = $xpath->query('//*[@id="public-hub-menu"]//a[@aria-current]');
            $this->assertCount($expected === null ? 0 : 1, $current, $routeName);
            if ($expected !== null) $this->assertSame($expected, trim($current->item(0)->textContent), $routeName);
            $this->assertCount(1, $xpath->query('//nav[@aria-label="PianoLIT hubs"]/p[normalize-space(.)="PianoLIT Hubs"]'));
        }
    }
}
