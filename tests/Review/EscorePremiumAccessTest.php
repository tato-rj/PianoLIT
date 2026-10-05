<?php

namespace Tests\Review;

use App\{Favorite, FavoriteFolder, Piece, Playlist, Tutorial, User};
use App\Billing\Membership;
use App\Billing\Sources\{Apple, Stripe};
use App\PDF\PDFGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Event, Redis};

class EscorePremiumAccessTest extends ReviewTestCase
{
    private $user, $folder, $playlist, $piece;

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Redis::shouldReceive('get')->andReturn(null);
        Event::fake([\App\Events\eScoreGenerated::class]);
        Model::withoutEvents(function () {
            $this->user = create(User::class, ['super_user' => false]);
            $this->folder = FavoriteFolder::create(['user_id' => $this->user->id, 'name' => 'My folder']);
            $this->playlist = create(Playlist::class, ['published_at' => now(), 'cover_path' => null]);
            $this->piece = create(Piece::class, ['score_path' => 'fixture.pdf', 'score_url' => null, 'is_free' => true]);
            create(Tutorial::class, ['piece_id' => $this->piece->id]);
            $this->playlist->pieces()->attach($this->piece->id);
            Favorite::create(['user_id' => $this->user->id, 'favorite_folder_id' => $this->folder->id, 'piece_id' => $this->piece->id, 'order' => 0]);
        });
    }

    private function urls()
    {
        return [route('webapp.users.favorites.folders.pdf', $this->folder), route('webapp.playlists.pdf', $this->playlist)];
    }

    private function subscription($sourceClass, $state)
    {
        if (!$sourceClass) return;
        $attributes = ['created_at' => now()->subMonth(), 'renews_at' => now()->addMonth()];
        if ($state === 'trial') $attributes = ['status' => 'trialing', 'created_at' => now(), 'renews_at' => now()->addDays(7)];
        if ($state === 'grace') $attributes += ['status' => 'canceled', 'membership_ends_at' => now()->addDay()];
        if ($state === 'expired') $attributes['renews_at'] = now()->subDay();
        if ($state === 'ended') $attributes['ended_at'] = now();
        if ($state === 'paused') $attributes['paused_at'] = now();
        if ($state === 'ended-grace') $attributes['membership_ends_at'] = now()->subSecond();
        Model::withoutEvents(function () use ($sourceClass, $attributes) {
            $source = create($sourceClass, $attributes);
            Membership::create(['user_id' => $this->user->id, 'source_type' => $sourceClass, 'source_id' => $source->id]);
        });
        $this->user->unsetRelation('membership');
    }

    public static function membershipStates()
    {
        return [
            'free account' => [null, null, false, false],
            'paid Stripe' => [Stripe::class, 'active', false, true],
            'paid Apple' => [Apple::class, 'active', false, true],
            'active trial' => [Stripe::class, 'trial', false, true],
            'cancellation grace period' => [Stripe::class, 'grace', false, true],
            'expired subscription' => [Stripe::class, 'expired', false, false],
            'ended subscription' => [Stripe::class, 'ended', false, false],
            'paused subscription' => [Stripe::class, 'paused', false, false],
            'completed grace period' => [Stripe::class, 'ended-grace', false, false],
            'super user without subscription' => [null, null, true, true],
            'super user with ended subscription' => [Stripe::class, 'ended', true, true],
        ];
    }

    /** @dataProvider membershipStates */
    public function test_preview_download_and_legacy_get_follow_premium_access($sourceClass, $state, $superUser, $allowed)
    {
        $this->subscription($sourceClass, $state);
        $this->user->updateQuietly(['super_user' => $superUser]);
        $this->actingAs($this->user, 'web');
        $generator = \Mockery::mock(PDFGenerator::class);
        if ($allowed) {
            $generator->shouldReceive('pieces', 'request', 'generate')->andReturnSelf();
            $generator->shouldReceive('output')->andReturn('%PDF-preview');
            $generator->shouldReceive('metadata')->andReturn(['pages' => 3]);
            $generator->shouldReceive('download', 'stream')->andReturn(response('%PDF-fixture'));
        } else {
            $generator->shouldNotReceive('pieces');
            $generator->shouldNotReceive('generate');
        }
        $this->app->instance(PDFGenerator::class, $generator);
        foreach ($this->urls() as $url) {
            foreach ([true, false] as $preview) {
                $response = $this->postJson($url, ['title' => 'Book', 'piece_ids' => [$this->piece->id], 'preview' => $preview, 'user_id' => 999999]);
                if ($allowed) $response->assertOk();
                else $response->assertForbidden()->assertJsonPath('message', 'Go Premium to create eScores.');
            }
            $response = $this->getJson($url.'?title=Book');
            if ($allowed) $response->assertOk();
            else $response->assertForbidden()->assertJsonPath('message', 'Go Premium to create eScores.');
        }
        if (!$allowed) Event::assertNotDispatched(\App\Events\eScoreGenerated::class);
    }

    public function test_guests_cannot_call_generation_or_preview_routes()
    {
        $generator = \Mockery::mock(PDFGenerator::class);
        $generator->shouldNotReceive('generate');
        $this->app->instance(PDFGenerator::class, $generator);
        foreach ($this->urls() as $url) {
            $this->get($url.'?title=Book')->assertRedirect(route('login'));
            foreach ([true, false] as $preview) $this->postJson($url, ['title' => 'Book', 'preview' => $preview, 'user_id' => $this->user->id])->assertUnauthorized();
        }
    }
}
