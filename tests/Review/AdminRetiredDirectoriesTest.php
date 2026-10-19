<?php

namespace Tests\Review;

use App\{Admin, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class AdminRetiredDirectoriesTest extends ReviewTestCase
{
    public function test_retired_location_and_log_routes_are_gone()
    {
        $this->actingAs(create(Admin::class), 'admin')->withExceptionHandling();
        foreach (['admin.users.locations.index', 'admin.users.locations.load-map', 'admin.users.logs',
            'admin.users.load-logs', 'admin.memberships.logs', 'admin.log.data', 'admin.analytics.logs',
            'admin.activity.index', 'admin.activity.show', 'admin.users.activity'] as $name) {
            $this->assertFalse(Route::has($name), $name.' must not remain registered.');
        }
        $host = 'http://admin.'.config('app.short_url');
        foreach (['users/locations', 'users/locations/load-map', 'users/logs', 'users/1/load-logs',
            'memberships/logs', 'logs/data', 'analytics/logs', 'activity',
            'activity/visits/00000000-0000-4000-8000-000000000000', 'users/1/activity'] as $path) {
            // A generic DELETE-only resource route can yield 405 for a retired
            // GET path. Neither routing error may return an old page or data.
            $this->assertContains($this->get($host.'/'.$path)->getStatusCode(), [404, 405], $path);
            $this->assertContains($this->getJson($host.'/'.$path, ['X-Requested-With' => 'XMLHttpRequest'])->getStatusCode(), [404, 405], $path);
        }
    }

    public function test_users_navigation_and_new_account_notifications_have_live_destinations()
    {
        $admin = create(Admin::class);
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($admin, 'admin');
        $response = $this->get(route('admin.users.index'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $links = (new \DOMXPath($dom))->query('//*[@id="users"]//a');
        $this->assertSame(['Profiles', 'Tutorial Requests', 'Feedback'], array_map(function ($link) {
            return trim($link->textContent);
        }, iterator_to_array($links)));
        foreach (['admin.users.index', 'admin.tutorial-requests.index', 'admin.feedback.index'] as $i => $name) {
            $this->assertSame(route($name), $links[$i]->getAttribute('href'));
        }
        $notification = (new \App\Notifications\NewUser($user))->toArray($admin);
        $this->assertSame(route('admin.users.show', $user), $notification['url']);
        $this->get($notification['url'])->assertOk();
        $this->get(route('admin.home'))->assertOk()->assertDontSee('/users/locations', false);
        foreach (['admin.analytics.users', 'admin.analytics.memberships'] as $name) {
            $this->assertTrue(Route::has($name));
        }
    }
}
