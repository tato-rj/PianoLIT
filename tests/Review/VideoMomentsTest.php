<?php

namespace Tests\Review;

use App\{Admin, Piece, Tag, Tutorial, User, VideoMoment};
use Illuminate\Database\Eloquent\Model;

class VideoMomentsTest extends ReviewTestCase
{
    protected $piece, $video, $otherVideo;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->piece = create(Piece::class);
            foreach (['level' => 'elementary', 'period' => 'baroque', 'length' => 'short'] as $type => $name) {
                $this->piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            }
            $this->video = create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'Performance']);
            $this->otherVideo = create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'Synthesia']);
        });
    }

    protected function moment($video = null, array $attributes = [])
    {
        return ($video ?? $this->video)->moments()->create(array_merge([
            'start_time' => 12.25, 'end_time' => 19.5, 'title' => 'Opening theme',
            'comment' => 'Listen to the main idea.', 'sort_order' => 0,
        ], $attributes));
    }

    protected function url($action = 'update', $video = null, $piece = null)
    {
        return route('admin.pieces.videos.moments.'.$action, [$piece ?? $this->piece, $video ?? $this->video]);
    }

    protected function payload(array $moments)
    {
        return ['revision' => hash('sha256', $this->video->moments()->get()->toJson()), 'moments' => $moments];
    }

    protected function row(array $attributes = [])
    {
        return array_merge(['start_time' => '1:23.125', 'end_time' => '1:29.5', 'title' => 'Theme', 'comment' => 'Listen here.'], $attributes);
    }

    public function test_empty_guides_have_no_ui_and_each_video_has_its_own_data()
    {
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertDontSee('Moments in this piece')->assertDontSee('data-video-moments=', false);
        $this->moment();
        $this->moment($this->otherVideo, ['title' => 'Synthesia only', 'end_time' => null]);
        $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertSee('Opening theme')->assertSee('Synthesia only')->assertSee('0:12')
            ->assertSee('data-media-preview="10"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'data-video-moments='));
        $this->get(route('webapp.pieces.tutorial', [$this->piece, $this->video]))->assertOk()
            ->assertSee('Opening theme')->assertDontSee('Synthesia only');
        $this->piece->updateQuietly(['is_free' => true]);
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()->assertDontSee('data-media-preview=', false);
    }

    public function test_moment_data_is_escaped_and_does_not_change_tutorial_mobile_serialization()
    {
        $title = '<img src=x onerror=alert(1)>';
        $this->moment(null, ['title' => $title, 'comment' => '</script><script>alert(1)</script>', 'start_time' => 3723]);
        $before = $this->video->toArray();
        $this->video->load('moments');
        $this->assertSame($before, $this->video->toArray());
        $this->assertArrayNotHasKey('moments', $this->video->toArray());
        $this->get(route('webapp.pieces.tutorial', [$this->piece, $this->video]))->assertOk()
            ->assertSee('1:02:03')->assertSee(e($title), false)->assertDontSee($title, false)
            ->assertDontSee('</script><script>alert(1)</script>', false);
    }

    public function test_manager_can_add_edit_delete_and_reorder_without_changing_ids()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->get($this->url('edit'))->assertOk()->assertSee('Add a moment')->assertSee('1:02:03');
        $this->get(route('admin.pieces.edit', $this->piece))->assertOk()->assertSee($this->url('edit'), false)->assertSee('Manage moments');
        $this->put($this->url(), $this->payload([$this->row(), $this->row(['start_time' => '12', 'end_time' => ''])]))->assertRedirect();
        $moments = $this->video->moments()->get();
        $this->assertSame(83.125, $moments[0]->start_time);
        $this->assertSame(89.5, $moments[0]->end_time);
        $this->assertNull($moments[1]->end_time);
        $this->put($this->url(), $this->payload([
            $this->row(['id' => $moments[1]->id, 'start_time' => '0', 'end_time' => '0', 'title' => 'First']),
            $this->row(['id' => $moments[0]->id, 'start_time' => '1:02:03.25', 'end_time' => '']),
        ]))->assertRedirect();
        $this->assertSame([$moments[1]->id, $moments[0]->id], $this->video->moments()->pluck('id')->all());
        $this->assertSame(0.0, $moments[1]->fresh()->end_time);
        $this->assertSame(3723.25, $moments[0]->fresh()->start_time);
        $this->put($this->url(), $this->payload([]))->assertRedirect();
        $this->assertDatabaseCount('video_moments', 0);
    }

    public function test_invalid_time_shapes_and_foreign_ids_leave_existing_data_intact()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $moment = $this->moment();
        $foreign = $this->moment($this->otherVideo);
        $this->withExceptionHandling();
        foreach (['-1', '1:99', '1:2', '1:60:01', 'NaN', '1.1234', '10000000'] as $time) {
            $this->putJson($this->url(), $this->payload([$this->row(['start_time' => $time])]))->assertUnprocessable()->assertJsonValidationErrors('moments.0.start_time');
        }
        $this->putJson($this->url(), $this->payload([$this->row(['end_time' => '2'])]))->assertUnprocessable()->assertJsonValidationErrors('moments.0.end_time');
        $this->putJson($this->url(), $this->payload([$this->row(['id' => $foreign->id])]))->assertUnprocessable();
        $this->putJson($this->url(), $this->payload([$this->row(['title' => ['bad']])]))->assertUnprocessable();
        $this->assertDatabaseCount('video_moments', 2);
        $this->assertSame('Opening theme', $moment->fresh()->title);
    }

    public function test_stale_tabs_cannot_overwrite_newer_changes()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $moment = $this->moment();
        $payload = $this->payload([$this->row(['id' => $moment->id])]);
        $moment->update(['title' => 'Newer edit']);
        $this->withExceptionHandling()->putJson($this->url(), $payload)->assertUnprocessable()->assertJsonValidationErrors('revision');
        $this->assertSame('Newer edit', $moment->fresh()->title);
    }

    public function test_failed_save_rolls_back_partial_edits()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $first = $this->moment();
        $second = $this->moment(null, ['sort_order' => 1]);
        $payload = $this->payload([$this->row(['id' => $first->id]), $this->row()]);
        VideoMoment::creating(function () { throw new \RuntimeException('Simulated storage failure'); });
        try {
            $this->put($this->url(), $payload);
            $this->fail('Save must fail.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated storage failure', $error->getMessage());
        } finally {
            VideoMoment::flushEventListeners();
        }
        $this->assertSame('Opening theme', $first->fresh()->title);
        $this->assertNotNull($second->fresh());
        $this->assertDatabaseCount('video_moments', 2);
    }

    public function test_admin_session_policy_and_piece_video_ownership_are_enforced()
    {
        $this->withExceptionHandling()->getJson($this->url('edit'))->assertUnauthorized();
        $this->putJson($this->url(), $this->payload([]))->assertUnauthorized();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web')->getJson($this->url('edit'))->assertUnauthorized();
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->get($this->url('edit'))->assertForbidden();
        $this->putJson($this->url(), $this->payload([]))->assertForbidden();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $otherPiece = Model::withoutEvents(function () { return create(Piece::class); });
        $this->get($this->url('edit', $this->video, $otherPiece))->assertNotFound();
        $this->putJson($this->url('update', $this->video, $otherPiece), $this->payload([]))->assertNotFound();
    }

    public function test_moment_saves_require_the_admin_session_csrf_token()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->app['env'] = 'review-csrf';
        $this->withExceptionHandling()->withSession(['_token' => 'moments-review-csrf']);
        $this->putJson($this->url(), $this->payload([$this->row()]))->assertStatus(419);
        $this->withHeader('X-CSRF-TOKEN', 'moments-review-csrf')
            ->put($this->url(), $this->payload([$this->row()]))->assertRedirect();
        $this->assertDatabaseCount('video_moments', 1);
    }

    public function test_deleting_a_video_cascades_its_moments_only()
    {
        $this->moment();
        $other = $this->moment($this->otherVideo);
        $this->video->delete();
        $this->assertDatabaseCount('video_moments', 1);
        $this->assertNotNull($other->fresh());
    }
}
