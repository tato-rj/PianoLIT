<?php

namespace Tests\Review;

use App\{Piece, Tag, Tutorial, User};
use App\Billing\Membership;
use App\Billing\Sources\{Apple, Stripe};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PieceContentAccessTest extends ReviewTestCase
{
    protected $piece, $tutorial;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->piece = create(Piece::class, [
                'score_path' => 'test-score.pdf', 'score_url' => null,
                'audio_path' => 'full.mp3', 'audio_path_rh' => 'right.mp3', 'audio_path_lh' => 'left.mp3',
            ]);
            foreach (['level' => 'elementary', 'period' => 'baroque', 'length' => 'short'] as $type => $name) {
                $this->piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            }
            $this->tutorial = create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'performance', 'video_url' => 'https://example.com/performance.mp4']);
            create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'synthesia', 'video_url' => 'https://example.com/synthesia.mp4']);
        });
        Storage::fake('public');
        Storage::disk('public')->put('test-score.pdf', 'test PDF fixture');
    }

    protected function userWithSubscription($sourceClass = null, array $attributes = [])
    {
        return Model::withoutEvents(function () use ($sourceClass, $attributes) {
            $user = create(User::class)->setAppends(['full_name']);
            if ($sourceClass) {
                $source = create($sourceClass, array_merge(['renews_at' => now()->addMonth(), 'created_at' => now()->subMonth()], $attributes));
                Membership::create(['user_id' => $user->id, 'source_id' => $source->id, 'source_type' => $sourceClass]);
            }
            return $user;
        });
    }

    protected function assertRestricted()
    {
        $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertSee('id="piece-performance"', false)->assertSee('id="piece-synthesia"', false)
            ->assertSee('id="score-preview"', false)->assertSee('score-preview-pages', false)
            ->assertSee('id="piece-upgrade-modal"', false)
            ->assertSee('/js/views/piece-access.js?id=', false)
            ->assertDontSee('Download score')->assertDontSee('<embed', false)
            ->assertDontSee('id="score-editor"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'data-media-preview="10"'));
        $audio = $this->get(route('webapp.pieces.audio', $this->piece))->assertOk();
        $this->assertSame(3, substr_count($audio->getContent(), 'data-media-preview="10"'));
        $this->get(route('webapp.pieces.tutorial', [$this->piece, $this->tutorial]))->assertOk()->assertSee('data-media-preview="10"', false);
        $this->withExceptionHandling()->get(route('webapp.pieces.score', $this->piece))->assertForbidden();
        return $response;
    }

    public function test_visitors_and_nonpaying_accounts_receive_the_same_content_restrictions()
    {
        $this->assertRestricted()->assertDontSee('data-manage="save-to"', false);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->actingAs($this->userWithSubscription(), 'web');
        $this->assertRestricted()->assertSee('data-manage="save-to"', false);
        $this->get(route('webapp.my-pieces'))->assertOk()->assertSee('SUGGESTIONS');
        $this->postJson(route('webapp.users.favorites.update', $this->piece))->assertOk();
        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_active_stripe_and_apple_subscribers_and_trials_keep_full_content()
    {
        foreach ([
            [Stripe::class, []],
            [Apple::class, []],
            [Stripe::class, ['status' => 'trialing', 'created_at' => now(), 'renews_at' => now()->addDays(7)]],
            [Stripe::class, ['status' => 'trial', 'created_at' => now(), 'renews_at' => now()->addDays(7)]],
            [Apple::class, ['created_at' => now(), 'renews_at' => now()->addDays(7)]],
        ] as [$source, $attributes]) {
            $this->actingAs($this->userWithSubscription($source, $attributes), 'web');
            $this->assertFullContent();
        }
    }

    protected function assertFullContent()
    {
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertDontSee('data-media-preview=', false)->assertDontSee('id="score-preview"', false)
            ->assertDontSee('id="piece-upgrade-modal"', false)->assertSee('Download score')->assertSee('id="score-pdf"', false)
            ->assertSee('id="score-editor"', false)->assertSee('data-tool="pen"', false);
        $this->get(route('webapp.pieces.audio', $this->piece))->assertOk()->assertDontSee('data-media-preview=', false);
        $this->get(route('webapp.pieces.tutorial', [$this->piece, $this->tutorial]))->assertOk()->assertDontSee('data-media-preview=', false);
        $this->get(route('webapp.pieces.score', $this->piece))->assertOk();
    }

    public function test_super_users_keep_full_content_regardless_of_subscription_state()
    {
        foreach ([
            [null, []],
            [Stripe::class, ['ended_at' => now()]],
            [Stripe::class, ['renews_at' => now()->subDay()]],
            [Apple::class, ['renews_at' => now()->subDay()]],
        ] as [$source, $attributes]) {
            $user = $this->userWithSubscription($source, $attributes);
            $user->updateQuietly(['super_user' => true]);
            $this->actingAs($user, 'web');
            $this->assertTrue($user->isAuthorized());
            $this->assertFullContent();

            // Removing the override must restore the normal subscription rules.
            $user->updateQuietly(['super_user' => false]);
            $this->assertRestricted();
        }
    }

    public function test_grace_period_keeps_full_content_until_cancellation_takes_effect()
    {
        foreach (['active', 'trialing', 'canceled', 'paused'] as $status) {
            $user = $this->userWithSubscription(Stripe::class, [
                'status' => $status,
                'canceled_at' => now(),
                'paused_at' => $status === 'paused' ? now() : null,
                'renews_at' => null,
                'membership_ends_at' => now()->addDays(3),
            ]);
            $this->actingAs($user, 'web');
            $this->assertFullContent();

            // A stale renewal date must not extend a completed grace period.
            $user->membership->source->updateQuietly([
                'membership_ends_at' => now()->subSecond(),
                'renews_at' => now()->addMonth(),
            ]);
            $this->assertRestricted();
        }
    }

    public function test_inactive_or_unpaid_subscription_states_do_not_unlock_content()
    {
        foreach ([
            ['status' => 'trialing', 'renews_at' => now()->subDay()],
            ['status' => 'trialing', 'paused_at' => now()],
            ['status' => 'trialing', 'ended_at' => now()],
            ['status' => 'past_due'],
            ['paused_at' => now()],
            ['renews_at' => now()->subDay()],
            ['ended_at' => now()],
            ['ended_at' => now(), 'membership_ends_at' => now()->addDays(3)],
        ] as $attributes) {
            $this->actingAs($this->userWithSubscription(Stripe::class, $attributes), 'web');
            $this->assertRestricted();
        }
        $this->actingAs($this->userWithSubscription(Apple::class, ['renews_at' => now()->subDay()]), 'web');
        $this->assertRestricted();
    }

    public function test_free_pick_flag_and_supplied_user_ids_do_not_bypass_content_rules()
    {
        $this->piece->updateQuietly(['is_free' => true]);
        $subscriber = $this->userWithSubscription(Stripe::class);
        $this->get(route('webapp.pieces.show', [$this->piece, 'user_id' => $subscriber->id]))
            ->assertOk()->assertSee('data-media-preview="10"', false);
        $this->assertRestricted();
    }

    public function test_tutorial_must_belong_to_the_requested_piece()
    {
        $other = Model::withoutEvents(function () { return create(Piece::class); });
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->get(route('webapp.pieces.tutorial', [$other, $this->tutorial]));
    }
}
