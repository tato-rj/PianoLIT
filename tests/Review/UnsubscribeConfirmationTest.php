<?php

namespace Tests\Review;

use App\{EmailList, Subscription};
use App\Events\Emails\Unsubscribed;
use Illuminate\Support\Facades\{Event, Redis};
use Stevebauman\Location\Facades\Location;

class UnsubscribeConfirmationTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
        Event::fake([Unsubscribed::class]);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    public static function browserHosts()
    {
        return array_map(function ($prefix) { return [$prefix]; }, ['', 'www.', 'my.', 'blog.', 'learn.', 'shop.', 'games.', 'metronome.']);
    }

    /** @dataProvider browserHosts */
    public function test_confirmation_is_standalone_and_refresh_returns_to_the_main_website($prefix)
    {
        $subscriber = Subscription::create(['email' => 'reader@example.test']);
        $list = EmailList::create(['name' => EmailList::NEWSLETTER, 'description' => 'Monthly news']);
        $otherList = EmailList::create(['name' => EmailList::FREE_PICK, 'description' => 'Free Picks']);
        $list->add($subscriber);
        $otherList->add($subscriber);
        $origin = 'http://'.$prefix.config('app.short_url');
        $link = $origin.'/subscriptions/'.$subscriber->email.'/unsubscribe/'.$list->id;

        $this->get($link)->assertRedirect('/subscriptions/unsubscribed')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertFalse($list->has($subscriber->email));
        $this->assertTrue($otherList->has($subscriber->email));
        Event::assertDispatchedTimes(Unsubscribed::class, 1);

        $confirmation = $origin.'/subscriptions/unsubscribed';
        $response = $this->get($confirmation)->assertOk()
            ->assertSee('You’re unsubscribed')
            ->assertSee('Your email has been removed from this list.')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSessionMissing('unsubscribe-confirmation');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//header | //footer | //nav')->length);
        $this->assertSame(1, $xpath->query('//main//img[@alt="PianoLIT"]')->length);
        $this->assertSame(1, $xpath->query('//main//h1')->length);
        $this->assertStringNotContainsString($subscriber->email, $response->getContent());

        $this->get($confirmation)->assertRedirect('http://localhost');
        $this->get($confirmation)->assertRedirect('http://localhost');
        Event::assertDispatchedTimes(Unsubscribed::class, 1);
    }

    public function test_confirmation_cannot_be_opened_without_unsubscribing_even_with_a_query_flag()
    {
        config(['app.url' => 'pianolit.test']);
        $this->get('https://my.localhost/subscriptions/unsubscribed?unsubscribe-confirmation=1')
            ->assertRedirect('https://pianolit.test')
            ->assertHeader('Cache-Control', 'no-store, private');
        Event::assertNotDispatched(Unsubscribed::class);
    }

    public function test_reopening_an_already_used_email_link_does_not_repeat_the_opt_out_event()
    {
        $subscriber = Subscription::create(['email' => 'reader@example.test']);
        $list = EmailList::create(['name' => EmailList::NEWSLETTER, 'description' => 'Monthly news']);
        $list->add($subscriber);
        $link = route('subscriptions.unsubscribe', [$subscriber, $list]);

        $this->get($link)->assertRedirect('/subscriptions/unsubscribed');
        $this->get('/subscriptions/unsubscribed')->assertOk();
        $this->get($link)->assertRedirect('/subscriptions/unsubscribed');
        $this->get('/subscriptions/unsubscribed')->assertOk();
        $this->get('/subscriptions/unsubscribed')->assertRedirect('http://localhost');
        Event::assertDispatchedTimes(Unsubscribed::class, 1);
    }

    public function test_legacy_stateless_endpoint_keeps_its_direct_response()
    {
        // The legacy API has no web middleware to share validation errors.
        $this->app['view']->share('errors', new \Illuminate\Support\ViewErrorBag);
        $this->post('/api/subscriptions/unsubscribe')->assertOk()
            ->assertHeaderMissing('Location');
        Event::assertNotDispatched(Unsubscribed::class);
    }
}
