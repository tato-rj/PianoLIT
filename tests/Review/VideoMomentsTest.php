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
            ->assertDontSee('Sections in this piece')->assertDontSee('data-video-moments=', false);
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

    public function test_piece_and_admin_videos_request_inline_playback_with_and_without_moments()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        foreach ([false, true] as $withMoments) {
            if ($withMoments) $this->moment();
            foreach ([
                route('webapp.pieces.show', $this->piece),
                route('webapp.pieces.tutorial', [$this->piece, $this->video]),
                $this->url('edit'),
            ] as $url) {
                $html = $this->get($url)->assertOk()->getContent();
                preg_match_all('/<video\b[^>]*>/i', $html, $matches);
                $this->assertNotEmpty($matches[0]);
                foreach ($matches[0] as $video) {
                    $this->assertMatchesRegularExpression('/\splaysinline(?:\s|>)/', $video);
                    $this->assertMatchesRegularExpression('/\swebkit-playsinline(?:\s|>)/', $video);
                }
            }
        }
    }

    public function test_section_labels_include_admin_validation_and_save_messages()
    {
        $this->moment();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->get($this->url('edit'))->assertOk()->assertSee('Sections · Performance')
            ->assertSee('Sections in this piece')->assertSee('Add a section')->assertSee('Save sections')
            ->assertSee('Delete section')
            ->assertDontSee('Moments ·')->assertDontSee('Moments in this piece')->assertDontSee('Delete moment');
        $this->withExceptionHandling();
        $response = $this->putJson($this->url(), $this->payload([$this->row(['title' => ''])]))
            ->assertUnprocessable()->assertJsonValidationErrors('moments.0.title');
        $this->assertStringContainsString('section title', $response->json('errors')['moments.0.title'][0]);
        $this->put($this->url(), $this->payload([$this->row()]))->assertRedirect()
            ->assertSessionHas('status', 'The video sections have been saved.');
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

    public function test_manager_can_add_edit_delete_and_automatically_sort_without_changing_ids()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->get($this->url('edit'))->assertOk()->assertSee('Add a section')->assertSee('MM:SS');
        $this->get(route('admin.pieces.edit', $this->piece))->assertOk()->assertSee($this->url('edit'), false)->assertSee('Manage sections');
        $this->put($this->url(), $this->payload([$this->row(), $this->row(['start_time' => '12', 'end_time' => ''])]))->assertRedirect();
        $moments = $this->video->moments()->get();
        $this->assertSame(83.125, $moments[1]->start_time);
        $this->assertSame(89.5, $moments[1]->end_time);
        $this->assertNull($moments[0]->end_time);
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
        $payload = $this->payload([$this->row(['id' => $first->id]), $this->row(['start_time' => '90', 'end_time' => '95'])]);
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

    public function test_edit_formats_times_without_losing_fractional_seconds()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->moment(null, ['start_time' => 3723.125, 'end_time' => 7200.5]);
        $this->get($this->url('edit'))->assertOk()->assertSee('value="62:03.125"', false)
            ->assertSee('value="120:00.5"', false)->assertSee('data-moment-time', false);
        $this->assertSame('00:00', VideoMoment::formatTimeInput(0));
        $this->assertSame('', VideoMoment::formatTimeInput(null));
        $this->assertSame('01:23.5', VideoMoment::formatTimeInput('1:23.5'));
        $this->assertSame('1:99', VideoMoment::formatTimeInput('1:99'));
    }

    public function test_overlaps_and_duplicate_starts_can_be_saved_and_previewed()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->withExceptionHandling();
        foreach ([
            [['00:12', '00:20'], ['00:19.999', '00:25']],
            [['00:19', '00:25'], ['00:12', '00:20']], // Input order is independent of time order.
            [['00:12', '00:40'], ['00:20', '00:21']], // Nested ranges.
            [['00:12', '00:12'], ['00:12', '']], // Duplicate starts, including empty/zero ranges.
            [['00:12', '00:20'], ['00:18', '']], // Optional end still cannot start inside a range.
        ] as $times) {
            $rows = array_map(function ($time) { return $this->row(['start_time' => $time[0], 'end_time' => $time[1]]); }, $times);
            $this->putJson($this->url(), $this->payload($rows))->assertRedirect()->assertSessionHasNoErrors();
            $this->assertDatabaseCount('video_moments', 2);
            $this->get($this->url('edit'))->assertOk()->assertSee('admin-moment-preview', false)
                ->assertSee('data-video-moments=', false)->assertSee('Sections in this piece')
                ->assertDontSee('Open this video');
        }
    }

    public function test_adjacent_ranges_and_bounded_optional_ends_can_be_saved()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->putJson($this->url(), $this->payload([
            $this->row(['start_time' => '00:19.5', 'end_time' => '00:25']),
            $this->row(['start_time' => '00:12.25', 'end_time' => '00:19.5']),
            $this->row(['start_time' => '00:25', 'end_time' => '']),
            $this->row(['start_time' => '00:26', 'end_time' => '00:30']),
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([12.25, 19.5, 25.0, 26.0], $this->video->moments()->pluck('start_time')->all());
    }

    public function test_admin_video_preview_without_moments_has_no_guide_ui()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->get($this->url('edit'))->assertOk()->assertSee('id="admin-moment-preview"', false)
            ->assertSee(e($this->video->video_url), false)->assertSee('cdn.plyr.io/3.7.8/plyr.js', false)
            ->assertDontSee('data-video-moments=', false)->assertDontSee('Sections in this piece');
    }

    public function test_legacy_rows_display_in_time_order_with_a_valid_revision_and_stable_ties()
    {
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $late = $this->moment(null, ['start_time' => 83, 'end_time' => null, 'sort_order' => 0]);
        $early = $this->moment(null, ['start_time' => 12, 'end_time' => null, 'sort_order' => 1]);
        $html = $this->get($this->url('edit'))->assertOk()->assertDontSee('Move section up')
            ->assertDontSee('Move section down')->assertSee('Increase start time by one second')->getContent();
        preg_match_all('/name="moments\[\d+\]\[start_time\]" value="([^"]*)"/', explode('<template', $html)[0], $times);
        $this->assertSame(['00:12', '01:23'], $times[1]);
        preg_match('/name="revision" value="([^"]*)"/', $html, $revision);
        $this->put($this->url(), ['revision' => $revision[1], 'moments' => [
            $this->row(['id' => $late->id, 'start_time' => '12', 'end_time' => '']),
            $this->row(['id' => $early->id, 'start_time' => '12', 'end_time' => '']),
        ]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([$late->id, $early->id], $this->video->moments()->pluck('id')->all());
        $this->assertSame([0, 1], $this->video->moments()->pluck('sort_order')->all());
    }
}
