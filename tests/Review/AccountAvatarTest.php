<?php

namespace Tests\Review;

use App\Auth\Google\Accounts;
use App\Services\Navigation\AccountAvatar;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountAvatarTest extends ReviewTestCase
{
    public function test_only_bounded_https_google_images_are_accepted()
    {
        $this->assertSame('https://lh3.googleusercontent.com/a/photo=s96-c', AccountAvatar::googleUrl('https://lh3.googleusercontent.com/a/photo=s96-c'));
        foreach ([null, [], '', 'javascript:alert(1)', 'data:image/svg+xml,test', 'http://lh3.googleusercontent.com/a/photo',
            'https://googleusercontent.com.attacker.test/photo', 'https://attacker.test/photo', 'https://127.0.0.1/photo',
            'https://user:secret@lh3.googleusercontent.com/photo', 'https://lh3.googleusercontent.com:8443/photo',
            'https://lh3.googleusercontent.com/photo#fragment', 'https://lh3.googleusercontent.com/'.str_repeat('a', 2048)] as $url) {
            $this->assertNull(AccountAvatar::googleUrl($url));
        }
    }

    public function test_photo_is_account_owned_separate_from_user_json_and_the_initial_remains_as_fallback()
    {
        $user = Model::withoutEvents(fn () => create(User::class, ['first_name' => 'Alex']));
        $other = Model::withoutEvents(fn () => create(User::class));
        $photo = 'https://lh3.googleusercontent.com/a/review-photo=s96-c';
        DB::transaction(fn () => app(Accounts::class)->link($user, ['sub' => 'review-photo-subject', 'email' => $user->email, 'picture' => $photo]));
        $service = app(AccountAvatar::class);
        $this->assertSame($photo, $service->forAccount($user));
        $this->assertNull($service->forAccount($other));
        $html = view('components.account.account-circle', ['account' => $user, 'menuAvatar' => true])->render();
        $this->assertStringContainsString('src="'.$photo.'"', $html);
        $this->assertStringContainsString('referrerpolicy="no-referrer"', $html);
        $this->assertStringContainsString('account-circle__initial">A</span>', $html);
        $this->assertArrayNotHasKey('profile_image_url', $user->toArray());
        $this->assertFalse($user->relationLoaded('auth_providers'));

        // Apple supplies no image, and connecting Apple cannot replace a Google photo.
        DB::transaction(fn () => app(Accounts::class)->link($other, ['sub' => 'review-apple-subject', 'email' => $other->email, 'picture' => $photo], null, 'apple'));
        $this->app->instance('request', Request::create('http://localhost/'));
        $this->assertNull($service->forAccount($other));
        Model::withoutEvents(fn () => $user->delete());
        $this->assertDatabaseMissing('user_auth_providers', ['user_id' => $user->id]);
        Model::withoutEvents(fn () => $other->delete());
    }

    public function test_actual_profile_shell_renders_the_compact_menu_for_visual_review()
    {
        config(['activity.enabled' => false, 'attribution.enabled' => false]);
        $this->withoutMiddleware(\App\Http\Middleware\UpdateLocation::class);
        $user = Model::withoutEvents(fn () => create(User::class, ['first_name' => 'Alex', 'last_name' => 'Morgan', 'email' => 'alex@example.test']));
        $photo = 'https://lh3.googleusercontent.com/a/visual-review=s96-c';
        DB::transaction(fn () => app(Accounts::class)->link($user, ['sub' => 'visual-review-subject', 'email' => $user->email, 'picture' => $photo]));
        $response = $this->actingAs($user, 'web')->get('http://localhost/profile')->assertOk()->assertSee('PianoLIT Hubs');
        $response->assertSee('data-account-avatar', false)->assertSee('referrerpolicy="no-referrer"', false);
        if ($directory = getenv('ACCOUNT_MENU_PREVIEW_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0700, true);
            $origin = 'http://127.0.0.1:8767';
            $html = str_replace(['http://localhost', $photo], [$origin, $origin.'/sample-photo.svg'], $response->getContent());
            file_put_contents($directory.'/profile.html', $html);
        }
    }
}
