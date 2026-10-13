<?php

namespace Tests\Review;

use App\{Admin, User, Piece};
use App\Activity\{Actions, Filters, Insights, Journey, People, Presentation, Queries, VisitSort};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Auth, Crypt, DB, Route};
use Illuminate\Support\Str;

class UserAnalyticsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Keep legacy persisted-history coverage alongside the default-gate suite.
        config(['activity.minimum_visit_seconds' => 0]);
        $this->travelTo(carbon('2026-10-12 19:30:00'));
        Route::middleware(['web', \App\Http\Middleware\Logs\RecordWebLog::class])->group(function () {
            Route::get('/review/journey', function () { return response('<html>Home</html>'); })->name('review.journey');
            Route::post('/review/journey-login/{user}', function (User $user) { Auth::guard('web')->login($user); return response()->noContent(); })->name('review.journey-login');
        });
    }

    private function account(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes)->setAppends(['full_name']); });
    }

    private function visit($user = null, $at = '2026-10-12 19:14:00', $platform = 'webapp', $visitor = null)
    {
        $id = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $id, 'user_id' => optional($user)->id, 'visitor_id' => $visitor ?: (string) Str::uuid(), 'platform' => $platform, 'started_at' => $at, 'last_activity_at' => carbon($at)->addMinutes(8)]);
        return $id;
    }

    private function page($visit, $route = 'webapp.discover', $at = '2026-10-12 19:14:00')
    {
        $v = DB::table('activity_visits')->where('id', $visit)->first();
        $id = (string) Str::uuid();
        DB::table('activity_pages')->insert(['id' => $id, 'visit_id' => $visit, 'user_id' => $v->user_id, 'visitor_id' => $v->visitor_id, 'platform' => $v->platform, 'route_name' => $route, 'url' => 'https://my.pianolit.test/'.str_replace('.', '/', $route), 'opened_at' => $at, 'elapsed_ms' => 15000, 'visible_ms' => 12000]);
        return $id;
    }

    private function event($visit, $page, $type, $at = '2026-10-12 19:14:01', array $metadata = [], $piece = null)
    {
        $v = DB::table('activity_visits')->where('id', $visit)->first();
        return DB::table('activity_events')->insertGetId(['event_id' => (string) Str::uuid(), 'visit_id' => $visit, 'page_id' => $page, 'user_id' => $v->user_id, 'visitor_id' => $v->visitor_id, 'platform' => $v->platform, 'type' => $type, 'source' => in_array($type, ['page_view', 'favorite_changed', 'annotation_saved'], true) ? 'server' : 'browser', 'occurred_at' => $at, 'received_at' => '2026-10-12 19:29:00', 'resource_type' => $piece ? 'piece' : null, 'resource_id' => $piece ? $piece->id : null, 'metadata' => json_encode($metadata)]);
    }

    private function filters(array $values = [])
    {
        return new Filters(\Illuminate\Http\Request::create('/analytics/users', 'GET', $values));
    }

    public function test_activity_people_insights_and_all_profile_tabs_render_real_records()
    {
        $user = $this->account(['first_name' => 'Maria', 'last_name' => 'Rossi']);
        $piece = Model::withoutEvents(function () { return create(Piece::class, ['name' => 'Clair de lune']); });
        $visit = $this->visit($user); $page = $this->page($visit, 'webapp.pieces.show');
        $this->event($visit, $page, 'page_view', '2026-10-12 19:14:00', [], $piece);
        $this->event($visit, $page, 'media_progress', '2026-10-12 19:15:00', ['media_kind' => 'video', 'playback_id' => (string) Str::uuid(), 'unique_watched_percent' => 80, 'played_seconds' => 144, 'piece_id' => $piece->id]);
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.users.people'))->assertOk();
        foreach (['admin.analytics.users', 'admin.analytics.users.insights'] as $route) {
            foreach ([null => 'registered', 'all' => 'all', 'anonymous' => 'anonymous'] as $audience => $expected) {
                $this->get(route($route, $audience ? ['audience' => $audience] : []))->assertOk()
                    ->assertViewHas('filters', function ($filters) use ($expected) { return $filters->values['audience'] === $expected; });
            }
        }
        foreach (['overview', 'visits', 'music', 'account'] as $tab) $this->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => $tab]))->assertOk()->assertSee('Maria Rossi');
        $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Clair de lune')->assertSee('80% unique content played')->assertSee('America/New_York')->assertDontSee('closed the browser');
        $this->assertDatabaseCount('activity_imports', 0);
    }

    public function test_confirmed_login_links_only_current_anonymous_visit_without_rewriting_history()
    {
        $user = $this->account();
        $this->get('/review/journey')->assertOk();
        $anonymous = DB::table('activity_visits')->first();
        $unrelated = $this->visit(null, '2026-10-12 18:00:00');
        $this->post('/review/journey-login/'.$user->id)->assertNoContent();
        $this->get('/review/journey')->assertOk();
        $this->assertDatabaseHas('activity_visit_links', ['visit_id' => $anonymous->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('activity_visit_links', ['visit_id' => $unrelated]);
        $this->assertDatabaseHas('activity_visits', ['id' => $anonymous->id, 'user_id' => null]);
        $q = new Queries;
        $this->assertSame(2, $q->visits(null, $user->id)->count());
        $metrics = $q->metrics($this->filters(['audience' => 'all']));
        $this->assertSame(2, (int) $metrics['Unique visitors'], 'Linked anonymous/account visits count once; unrelated visitor remains distinct.');
        $this->assertDatabaseHas('activity_events', ['type' => 'login', 'user_id' => $user->id]);
    }

    public function test_filters_and_returning_counts_do_not_include_legacy_daily_totals()
    {
        $user = $this->account();
        $this->visit($user, '2026-10-10 10:00:00');
        $this->visit($user, '2026-10-12 10:00:00');
        $this->visit(null, '2026-10-12 10:00:00', 'web');
        $this->visit(null, '2026-10-11 10:00:00', 'app');
        $q = new Queries;
        $default = $q->metrics($this->filters());
        $this->assertSame(2, (int) $default['Visits']);
        $this->assertSame(1, (int) $default['Unique visitors']);
        $this->assertSame(4, (int) $q->metrics($this->filters(['audience' => 'all']))['Visits']);
        $this->assertSame(2, (int) $q->metrics($this->filters(['audience' => 'anonymous']))['Visits']);
        $metrics = $q->metrics($this->filters(['range' => 'today', 'platform' => 'webapp', 'audience' => 'registered']));
        $this->assertSame(1, (int) $metrics['Unique visitors']);
        $this->assertSame(1, (int) $metrics['Visits']);
        $this->assertSame(1, (int) $metrics['Returning visitors']);
        $this->assertSame(1, $q->visits($this->filters(['range' => 'yesterday', 'audience' => 'all']))->count());
        $this->assertSame(1, $q->visits($this->filters(['range' => 'today', 'audience' => 'anonymous']))->count());
    }

    public function test_journey_orders_actions_by_occurrence_not_receipt_and_does_not_invent_page_associations()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $late = $this->event($visit, $page, 'click', '2026-10-12 19:16:00', ['label' => 'Favorites']);
        $early = $this->event($visit, $page, 'filter_change', '2026-10-12 19:15:00', ['value' => 'Intermediate']);
        $unplaced = $this->event($visit, null, 'score_opened', null);
        $data = (new Journey)->get($visit);
        $this->assertSame([$early, $late], $data['groups'][0]['actions']->pluck('id')->all());
        $this->assertSame([$unplaced], collect($data['unplaced']->items())->pluck('id')->all());
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Actions without a recorded page')->assertSee('receipt time; occurrence unknown');
    }

    public function test_journey_collapses_duplicate_starts_and_transitions_into_one_measured_playback()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $piece = Model::withoutEvents(function () { return create(Piece::class, ['name' => 'A sad song']); });
        $metadata = ['media_kind' => 'video', 'media_id' => 'piece-performance', 'piece_id' => $piece->id, 'playback_id' => (string) Str::uuid(), 'duration_seconds' => 88.468027, 'played_seconds' => 0];
        $this->event($visit, $page, 'media_play', '2026-10-12 19:15:00', $metadata);
        $this->event($visit, $page, 'media_play', '2026-10-12 19:15:00', array_merge($metadata, ['played_seconds' => 0.517]));
        $this->event($visit, $page, 'media_pause', '2026-10-12 19:15:01', array_merge($metadata, ['played_seconds' => 0.517, 'unique_watched_percent' => 0.417]));
        $this->event($visit, $page, 'media_seek', '2026-10-12 19:15:02', $metadata);
        $data = (new Journey)->get($visit);
        $this->assertCount(1, $data['groups'][0]['media']);
        $playback = $data['groups'][0]['media']->first();
        $this->assertSame(0.517, $playback['watched'], 'Cumulative observations use the maximum, not the sum or media length.');
        $this->assertSame(88.468027, $playback['duration']);
        $this->assertTrue($playback['seeked']);
        $this->assertCount(0, $data['groups'][0]['actions']);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Less than 1s playing')->assertDontSee('Full media length:')->assertSee('A sad song')->assertDontSee('Started performance video');
        $this->assertSame(1, substr_count($response->getContent(), 'Played performance video'));
        foreach (['media_progress', 'media_pause', 'media_end'] as $type) {
            $this->event($visit, $page, $type, '2026-10-12 19:16:29', array_merge($metadata, ['played_seconds' => 89, 'unique_watched_percent' => 100]));
        }
        $response = $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('1m 29s playing')->assertSee('100% unique content played')
            ->assertDontSee('Paused performance video')->assertDontSee('Reached the end of performance video')->assertDontSee('Seeking was recorded')
            ->assertSeeInOrder(['class="ua-card ua-summary"', 'class="ua-card ua-identity"'], false)->assertSee('Visit summary');
        $this->assertSame(1, substr_count($response->getContent(), 'Played performance video'));
        $this->assertSame(1, substr_count($response->getContent(), '<meter min="0" max="100" value="100"'));
        $this->assertSame(1, substr_count($response->getContent(), 'class="ua-card ua-summary"'));
        $this->event($visit, $page, 'score_opened', '2026-10-12 19:14:30');
        $this->event($visit, $page, 'favorite_changed', '2026-10-12 19:15:30', ['result' => true]);
        $moments = (new Journey)->get($visit)['groups'][0]['moments'];
        $this->assertSame(['score_opened', 'media_play', 'favorite_changed'], $moments->pluck('event.type')->all(), 'The consolidated playback stays at its first time among other actions.');
        $this->event($visit, $page, 'media_play', '2026-10-12 19:16:00', array_merge($metadata, ['playback_id' => (string) Str::uuid()]));
        $this->assertCount(2, (new Journey)->get($visit)['groups'][0]['media'], 'Separate playbacks are not hidden.');
    }

    public function test_preview_play_clicks_fold_only_into_subsequent_playback_for_the_same_page_and_preview()
    {
        $visit = $this->visit(); $page = $this->page($visit, 'webapp.find-your-match');
        $otherPage = $this->page($visit, 'webapp.find-your-match', '2026-10-12 19:14:30');
        [$piece, $otherPiece] = Model::withoutEvents(function () { return [create(Piece::class, ['name' => 'Humming bird']), create(Piece::class, ['name' => 'Prelude'])]; });
        $click = ['control' => 'match_piece', 'label' => 'Play preview: Humming bird', 'piece_id' => $piece->id];
        $successfulClick = $this->event($visit, $page, 'click', '2026-10-12 19:15:00', $click);
        $otherPageClick = $this->event($visit, $otherPage, 'click', '2026-10-12 19:15:00', $click);
        $otherPreviewClick = $this->event($visit, $page, 'click', '2026-10-12 19:15:01', array_merge($click, ['piece_id' => $otherPiece->id, 'label' => 'Play preview: Prelude']));
        $choice = $this->event($visit, $page, 'click', '2026-10-12 19:15:02', array_merge($click, ['label' => 'Choose this piece']));
        $media = ['media_kind' => 'audio', 'media_id' => 'match-preview-'.$piece->id, 'piece_id' => $piece->id, 'playback_id' => (string) Str::uuid(), 'played_seconds' => 0];
        $this->event($visit, $page, 'media_play', '2026-10-12 19:15:03', $media);
        $this->event($visit, $page, 'media_progress', '2026-10-12 19:15:07', array_merge($media, ['played_seconds' => 4, 'unique_watched_percent' => 7]));
        // Playing another media source does not establish that its preview played.
        $this->event($visit, $page, 'media_play', '2026-10-12 19:15:08', ['media_kind' => 'audio', 'media_id' => 'piece-performance', 'piece_id' => $otherPiece->id]);
        $laterAttempt = $this->event($visit, $page, 'click', '2026-10-12 19:16:00', $click);
        $this->event($visit, $page, 'media_error', '2026-10-12 19:16:01', array_merge($media, ['playback_id' => (string) Str::uuid()]));
        $groups = (new Journey)->get($visit)['groups']->keyBy('page.id');
        $this->assertSame([$otherPreviewClick, $choice, $laterAttempt], $groups[$page]['actions']->pluck('id')->all());
        $this->assertSame([$otherPageClick], $groups[$otherPage]['actions']->pluck('id')->all());
        $this->assertNotContains($successfulClick, $groups[$page]['moments']->pluck('event.id')->all());
        $this->assertCount(2, $groups[$page]['media'], 'The failed preview load must not create another Played audio item.');
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('4s playing')->assertSee('7% unique content played')->assertSee('Playback attempted')->assertDontSee('Selected Play preview');
        $this->assertSame(3, substr_count($response->getContent(), 'Playback attempted'));
        $document = new \DOMDocument; @$document->loadHTML($response->getContent());
        $this->assertSame(2, (new \DOMXPath($document))->query('//strong[@class="ua-media-label"]/span[text()="Played audio"]')->length);
        $this->assertDatabaseHas('activity_events', ['id' => $successfulClick]);
    }

    public function test_preview_load_errors_without_playback_remain_attempts()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $click = $this->event($visit, $page, 'click', '2026-10-12 19:15:00', ['control' => 'match_piece', 'label' => 'Play preview: Prelude', 'piece_id' => 42]);
        $this->event($visit, $page, 'media_error', '2026-10-12 19:15:01', ['media_id' => 'match-preview-42', 'media_kind' => 'audio', 'piece_id' => 42, 'played_seconds' => 0, 'unique_watched_percent' => 0]);
        $group = (new Journey)->get($visit)['groups'][0];
        $this->assertSame([$click], $group['actions']->pluck('id')->all());
        $this->assertCount(0, $group['media']);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Playback attempted')->assertDontSee('Played audio')->assertDontSee('<meter', false);
        $this->assertSame(1, substr_count($response->getContent(), 'Playback attempted'));
    }

    public function test_preview_progress_can_confirm_playback_when_the_start_was_not_recorded()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $this->event($visit, $page, 'click', '2026-10-12 19:15:00', ['control' => 'match_piece', 'label' => 'Play preview: Prelude', 'piece_id' => 42]);
        $this->event($visit, $page, 'media_pause', '2026-10-12 19:15:04', ['media_id' => 'match-preview-42', 'media_kind' => 'audio', 'piece_id' => 42, 'played_seconds' => 4, 'unique_watched_percent' => 7]);
        $group = (new Journey)->get($visit)['groups'][0];
        $this->assertCount(0, $group['actions']);
        $this->assertCount(1, $group['media']);
        $this->assertSame(4, $group['media']->first()['watched']);
    }

    public function test_journey_nests_known_match_actions_and_keeps_other_activity_and_reopenings_separate()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $before = $this->event($visit, $page, 'click', '2026-10-12 19:14:00', ['label' => 'Explore repertoire']);
        $open = $this->event($visit, $page, 'click', '2026-10-12 19:15:00', ['label' => 'Find your match', 'control' => 'tour-button', 'target' => 'https://my.pianolit.test/tour']);
        $this->event($visit, $page, 'tab_open', '2026-10-12 19:15:01', ['section' => 'match-tour-modal']);
        $play = $this->event($visit, $page, 'media_play', '2026-10-12 19:15:02', ['media_kind' => 'audio', 'media_id' => 'match-preview-42', 'piece_id' => 42, 'playback_id' => (string) Str::uuid(), 'played_seconds' => 4, 'unique_watched_percent' => 7]);
        $choice = $this->event($visit, $page, 'click', '2026-10-12 19:15:06', ['label' => 'PreludeJ.S. BachSelect', 'control' => 'match_piece', 'piece_id' => 42]);
        $confirm = $this->event($visit, $page, 'click', '2026-10-12 19:15:07', ['label' => 'I like this one', 'control' => 'button']);
        $this->event($visit, $page, 'click', '2026-10-12 19:15:08', ['label' => 'Close Find your match', 'control' => 'button']);
        $outside = $this->event($visit, $page, 'favorite_changed', '2026-10-12 19:15:09', ['result' => true]);
        $reopen = $this->event($visit, $page, 'click', '2026-10-12 19:16:00', ['label' => 'Find your match', 'control' => 'tour-button']);
        $attempt = $this->event($visit, $page, 'click', '2026-10-12 19:16:01', ['label' => 'Play preview: Prelude', 'control' => 'match_piece', 'piece_id' => 42]);
        $group = (new Journey)->get($visit)['groups'][0];
        $display = $group['displayMoments'];
        $this->assertCount(4, $display);
        $this->assertSame([$before, $open, $outside, $reopen], $display->pluck('event.id')->all());
        $this->assertSame([$play, $choice, $confirm], $display[1]['children']->pluck('event.id')->all());
        $this->assertSame([$attempt], $display[3]['children']->pluck('event.id')->all());
        $this->assertNotNull($display[1]['section']['closed']);
        $this->assertNull($display[3]['section']['closed']);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Confirmed preferred piece')->assertSee('Selected a piece')->assertDontSee('Selected Find your match')->assertDontSee('Selected PreludeJ.S. BachSelect');
        $dom = new \DOMDocument(); @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $contexts = $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," ua-context-card ")]');
        $this->assertCount(2, $contexts);
        $this->assertStringContainsString('Played audio', $contexts[0]->textContent);
        $this->assertStringContainsString('Confirmed preferred piece', $contexts[0]->textContent);
        $this->assertStringNotContainsString('Added piece to Favorites', $contexts[0]->textContent);
        $this->assertStringContainsString('Playback attempted', $contexts[1]->textContent);
    }

    public function test_implicit_match_contexts_use_recorded_identifiers_and_do_not_take_unrelated_buttons()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $first = $this->event($visit, $page, 'click', '2026-10-12 19:15:00', ['section' => 'find-your-match', 'label' => 'Back', 'control' => 'button']);
        $confirm = $this->event($visit, $page, 'click', '2026-10-12 19:15:01', ['section' => 'find-your-match', 'label' => 'I like this one', 'control' => 'match_preference_confirm', 'piece_id' => 42]);
        $unrelated = $this->event($visit, $page, 'click', '2026-10-12 19:15:02', ['label' => 'Account settings', 'control' => 'button']);
        $legacy = $this->event($visit, $page, 'click', '2026-10-12 19:15:03', ['label' => 'I like this one', 'control' => 'button']);
        $display = (new Journey)->get($visit)['groups'][0]['displayMoments'];
        $this->assertCount(3, $display);
        $this->assertSame([$first, $confirm], $display[0]['children']->pluck('event.id')->all());
        $this->assertSame($unrelated, $display[1]['event']->id);
        $this->assertArrayNotHasKey('section', $display[1]);
        $this->assertSame([$legacy], $display[2]['children']->pluck('event.id')->all());
        $this->assertNull($display[0]['section']['opener'], 'Missing opening history must not be invented.');
    }

    public function test_failed_attempts_are_not_folded_into_a_later_match_opening()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $this->event($visit, $page, 'section_open', '2026-10-12 19:15:00', ['section' => 'find-your-match']);
        $attempt = $this->event($visit, $page, 'click', '2026-10-12 19:15:01', ['control' => 'match_piece', 'label' => 'Play preview: Prelude', 'piece_id' => 42]);
        $this->event($visit, $page, 'section_close', '2026-10-12 19:15:02', ['section' => 'find-your-match']);
        $this->event($visit, $page, 'section_open', '2026-10-12 19:16:00', ['section' => 'find-your-match']);
        $this->event($visit, $page, 'media_play', '2026-10-12 19:16:01', ['media_kind' => 'audio', 'media_id' => 'match-preview-42', 'piece_id' => 42, 'playback_id' => (string) Str::uuid()]);
        $group = (new Journey)->get($visit)['groups'][0];
        $this->assertCount(2, $group['displayMoments']);
        $this->assertSame([$attempt], $group['displayMoments'][0]['children']->pluck('event.id')->all());
        $this->assertNotNull($group['displayMoments'][1]['children']->first()['playback']);
    }

    public function test_playback_links_reuse_medium_names_with_composers_without_extra_queries()
    {
        [$piece, $orphan] = Model::withoutEvents(function () {
            $composer = create(\App\Composer::class, ['name' => 'Frederic Chopin']);
            $attributes = ['name' => 'Prelude', 'nickname' => null, 'movement_number' => null, 'key' => 'E minor',
                'catalogue_name' => 'Op.', 'catalogue_number' => '28', 'collection_name' => '24 Preludes', 'collection_number' => '4'];
            return [create(Piece::class, array_merge($attributes, ['composer_id' => $composer->id])),
                create(Piece::class, array_merge($attributes, ['composer_id' => 987654]))];
        });
        $expected = $piece->medium_name_with_composer;
        DB::enableQueryLog(); DB::flushQueryLog();
        $pieces = Queries::pieces(collect([$piece->id, $orphan->id]));
        $this->assertCount(1, DB::getQueryLog()); DB::disableQueryLog();
        $this->assertSame($expected, $pieces[$piece->id]->medium_name_with_composer);
        $this->assertSame('Prelude Op.28 No.4 by F. Chopin', $expected);
        $this->assertSame('Prelude Op.28 No.4', $pieces[$orphan->id]->medium_name_with_composer);
        $this->assertSame('Frederic Chopin', $pieces[$piece->id]->composer, 'Existing plain composer labels remain intact.');
        $this->assertFalse(property_exists($pieces[$piece->id], 'audio'), 'Unrelated appended model fields are not serialized.');
        $playback = ['event' => (object) ['type' => 'media_play', 'metadata' => '{"media_kind":"audio"}', 'resource_type' => 'piece', 'resource_id' => $piece->id],
            'label' => 'Played audio', 'started' => now(), 'watched' => 14, 'coverage' => 11];
        $html = view('admin.pages.analytics.users.playback', ['playback' => $playback, 'pieces' => $pieces, 'group' => ['piece' => null]])->render();
        $dom = new \DOMDocument(); @$dom->loadHTML($html);
        $links = $dom->getElementsByTagName('a');
        $this->assertCount(1, $links);
        $this->assertSame($expected, $links[0]->textContent);
        $this->assertSame(route('webapp.pieces.show', $piece->id), $links[0]->getAttribute('href'));
        $this->assertSame('_blank', $links[0]->getAttribute('target'));
        $this->assertSame('noopener noreferrer', $links[0]->getAttribute('rel'));
    }

    public function test_piece_selection_titles_resolve_destinations_and_preserve_original_details()
    {
        [$piece, $other] = Model::withoutEvents(function () {
            $composer = create(\App\Composer::class, ['name' => 'Yuri Levitin']);
            $attributes = ['name' => 'A Sad Song', 'composer_id' => $composer->id, 'nickname' => null, 'key' => 'Atonal',
                'catalogue_name' => null, 'catalogue_number' => null, 'collection_name' => null, 'collection_number' => null, 'movement_number' => null];
            return [create(Piece::class, $attributes), create(Piece::class, array_merge($attributes, ['name' => 'Another piece']))];
        });
        $visit = $this->visit(); $page = $this->page($visit);
        $original = 'A Sad Song Y. Levitin Early beginner Video Audio Score Synthesia';
        $metadata = ['label' => $original, 'control' => 'a', 'navigation' => '/', 'target' => 'https://my.pianolit.com/pieces/'.$piece->id, 'piece_id' => null];
        $old = $this->event($visit, $page, 'click', '2026-10-12 19:15:00', $metadata);
        $wrongSource = $this->event($visit, $page, 'click', '2026-10-12 19:15:01', array_merge($metadata, ['piece_id' => $other->id]), $other);
        $unplaced = $this->event($visit, null, 'click', '2026-10-12 19:15:02', $metadata);
        DB::enableQueryLog(); DB::flushQueryLog();
        $data = (new Journey)->get($visit);
        $pieceReads = collect(DB::getQueryLog())->filter(function ($query) { return strpos($query['query'], '"pieces" as "p"') !== false; });
        $this->assertCount(1, $pieceReads, 'All selected pieces use the existing grouped lookup.'); DB::disableQueryLog();
        foreach ([$old, $wrongSource, $unplaced] as $id) {
            $event = DB::table('activity_events')->find($id);
            $this->assertSame('Selected A Sad Song by Y. Levitin', Presentation::action($event, $data['pieces']));
            $this->assertSame($original, Presentation::metadata($event)['label']);
        }
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.journey', $visit))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $titles = $xpath->query('//div[contains(@class, "ua-action-line")]/strong | //article[contains(@class, "ua-page")]/h2');
        $selected = [];
        foreach ($titles as $title) if (strpos($title->textContent, 'Selected ') === 0) $selected[] = $title->textContent;
        $this->assertSame(array_fill(0, 3, 'Selected A Sad Song by Y. Levitin'), $selected);
        $this->assertStringContainsString($original, $xpath->query('//details[contains(@class, "ua-details")]/pre')->item(0)->textContent);
        $this->assertSame(3, DB::table('activity_events')->where('visit_id', $visit)->count(), 'Rendering never rewrites or deletes history.');
    }

    public function test_piece_selection_resolution_leaves_other_controls_and_unavailable_pieces_safe()
    {
        $event = (object) ['type' => 'click', 'metadata' => '{}'];
        $base = ['label' => 'A piece Beginner Video', 'control' => 'a', 'navigation' => '/', 'target' => '/pieces/178'];
        foreach (['/pieces/178', 'https://my.pianolit.com/pieces/178?audio_only=1#score', route('webapp.pieces.show', 178)] as $target) {
            $event->metadata = json_encode(array_merge($base, ['target' => $target]));
            $this->assertSame(178, Presentation::pieceSelectionId($event));
            $this->assertSame('Selected a piece', Presentation::action($event), 'Deleted/missing pieces never reuse noisy HTML labels.');
        }
        foreach ([
            ['target' => 'https://external.example/pieces/178'], ['target' => '/pieces/178/score'],
            ['target' => '/pieces/0'], ['target' => '/pieces/999999999999999999999'], ['target' => 'javascript:/pieces/178'],
            ['target' => 'https://user@my.pianolit.com/pieces/178'], ['navigation' => '/pieces/178'],
            ['control' => 'favorite'], ['label' => 'Score', 'target' => '/pieces/178', 'navigation' => '/pieces/178'],
        ] as $changes) {
            $metadata = array_merge($base, $changes); $event->metadata = json_encode($metadata);
            $this->assertNull(Presentation::pieceSelectionId($event));
            $this->assertSame('Selected '.$metadata['label'], Presentation::action($event));
        }
        $event->type = 'filter_change'; $event->metadata = json_encode($base + ['value' => 'Early beginner']);
        $this->assertNull(Presentation::pieceSelectionId($event));
        $this->assertSame('Selected Early beginner', Presentation::action($event));
    }

    public function test_partial_visits_keep_timing_unknown_and_technical_details_exclude_secrets()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $this->event($visit, $page, 'click', '2026-10-12 19:15:00', ['label' => '<script>bad</script>', 'password' => 'private-password', 'token' => 'private-token', 'marks' => 'private-annotation', 'filters' => ['email' => 'private@example.test', 'level' => 'beginner', 'notes' => 'private-notes', 'nested' => ['marks' => 'private-marks']]]);
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.users.journey', $visit))->assertOk()->assertSee('Page visibility was not recorded')->assertSee('&lt;script&gt;bad&lt;/script&gt;', false)->assertDontSee('private-password')->assertDontSee('private-token')->assertDontSee('private-annotation')->assertDontSee('private@example.test')->assertDontSee('private-notes')->assertDontSee('private-marks');
    }

    public function test_visit_pagination_keeps_multiple_same_day_visits_separate_and_queries_bounded()
    {
        $user = $this->account();
        for ($i = 0; $i < 33; $i++) $this->visit($user, carbon('2026-10-12 10:00:00')->addMinutes($i)->toDateTimeString());
        DB::enableQueryLog(); DB::flushQueryLog();
        $visits = (new Queries)->visitList($this->filters());
        $count = count(DB::getQueryLog()); DB::disableQueryLog();
        $this->assertCount(12, $visits);
        $this->assertSame(33, $visits->total());
        $this->assertLessThanOrEqual(5, $count);
        $this->assertCount(12, $visits->pluck('id')->unique());
    }

    public function test_visit_sorts_apply_before_pagination_with_stable_ties_and_bounded_queries()
    {
        $user = $this->account(); $records = [];
        for ($i = 0; $i < 30; $i++) {
            $start = carbon('2026-10-12 10:00:00')->addMinutes($i);
            $id = $this->visit($user, $start->toDateTimeString());
            $duration = $i === 29 ? null : ($i === 0 ? 100000 : (($i * 7) % 17 + 1) * 1000 + ($i % 3) * 100);
            $pages = $i === 0 ? 7 : $i % 4;
            DB::table('activity_visits')->where('id', $id)->update(['last_activity_at' => $start->copy()->addMilliseconds($duration ?: 0)->format('Y-m-d H:i:s.v')]);
            for ($p = 0; $p < $pages; $p++) $this->page($id);
            $records[] = ['id' => $id, 'start' => $start->toDateTimeString(), 'duration' => $duration, 'pages' => $pages];
        }
        foreach (['newest', 'oldest', 'longest', 'shortest', 'most_pages', 'fewest_pages'] as $value) {
            $sort = new VisitSort(\Illuminate\Http\Request::create('/', 'GET', ['visit_sort' => $value]));
            $expected = collect($records)->sort(function ($a, $b) use ($value) {
                if ($value === 'newest' || $value === 'oldest') return ($value === 'oldest' ? 1 : -1) * (strcmp($a['start'], $b['start']) ?: strcmp($a['id'], $b['id']));
                $key = in_array($value, ['longest', 'shortest'], true) ? 'duration' : 'pages';
                if (($a[$key] === null) !== ($b[$key] === null)) return $a[$key] === null ? 1 : -1;
                $direction = in_array($value, ['shortest', 'fewest_pages'], true) ? 1 : -1;
                return $direction * ($a[$key] <=> $b[$key]) ?: (strcmp($b['start'], $a['start']) ?: strcmp($b['id'], $a['id']));
            })->values();
            DB::enableQueryLog(); DB::flushQueryLog();
            $visits = (new Queries)->visitList(null, $user->id, 5, $sort);
            $queryCount = count(DB::getQueryLog()); DB::disableQueryLog();
            $this->assertSame($expected->take(5)->pluck('id')->all(), $visits->pluck('id')->all(), $value);
            $this->assertSame(30, $visits->total());
            $this->assertLessThanOrEqual(5, $queryCount);
            if (in_array($value, ['longest', 'shortest'], true)) {
                $all = (new Queries)->visitList(null, $user->id, 40, $sort);
                $this->assertSame($records[29]['id'], $all->last()->id, 'Unknown durations stay last in both directions.');
                $this->assertNull($all->last()->visit_duration_ms);
                $this->assertSame((float) $expected->first()['duration'], (float) $all->first()->visit_duration_ms);
            }
        }
        $this->assertSame(array_reverse(array_column(array_slice($records, -5), 'id')), (new Queries)->visitList(null, $user->id, 5)->pluck('id')->all(), 'Omitting the sort retains newest first.');
    }

    public function test_duration_sort_prefers_canonical_elapsed_time_across_daylight_saving()
    {
        $user = $this->account();
        $fold = $this->visit($user, '2026-11-01 01:30:00');
        DB::table('activity_visits')->where('id', $fold)->update(['last_activity_at' => '2026-11-01 01:15:00', 'started_at_utc' => '2026-11-01 05:30:00.000', 'last_activity_at_utc' => '2026-11-01 06:15:00.000']);
        $normal = $this->visit($user, '2026-11-01 03:00:00');
        $invalid = $this->visit($user, '2026-11-01 04:00:00');
        DB::table('activity_visits')->where('id', $invalid)->update(['last_activity_at' => '2026-11-01 03:59:00']);
        foreach (['longest' => [$fold, $normal, $invalid], 'shortest' => [$normal, $fold, $invalid]] as $value => $expected) {
            $visits = (new Queries)->visitList(null, $user->id, 25, new VisitSort(\Illuminate\Http\Request::create('/', 'GET', ['visit_sort' => $value])));
            $this->assertSame($expected, $visits->pluck('id')->all());
            $this->assertSame('45m 0s', \App\Activity\Time::visitDuration($visits->firstWhere('id', $fold)));
            $this->assertNull($visits->last()->visit_duration_ms);
        }
    }

    public function test_action_sort_counts_visible_tags_instead_of_raw_events_or_piece_totals()
    {
        $user = $this->account(); $piece = Model::withoutEvents(function () { return create(Piece::class); });
        $rich = $this->visit($user, '2026-10-12 10:00:00'); $page = $this->page($rich);
        for ($i = 0; $i < 4; $i++) $this->event($rich, $page, 'page_view', '2026-10-12 10:00:00', [], $piece);
        for ($i = 0; $i < 8; $i++) $this->event($rich, $page, 'media_play', '2026-10-12 10:01:00', ['media_kind' => 'audio']);
        foreach (['video', null] as $kind) $this->event($rich, $page, 'media_play', '2026-10-12 10:01:00', ['media_kind' => $kind]);
        foreach (['score_opened', 'annotation_saved', 'folder_created', 'trial_started', 'subscription_paid', 'premium_opened'] as $type) $this->event($rich, $page, $type);
        foreach ([['result' => true], ['result' => '1'], ['result' => false], [], ['folder_id' => 42, 'result' => true], ['folder_id' => 'null', 'result' => false]] as $metadata) $this->event($rich, $page, 'favorite_changed', '2026-10-12 10:02:00', $metadata);
        $noisy = $this->visit($user, '2026-10-12 19:00:00'); $noisyPage = $this->page($noisy);
        for ($i = 0; $i < 45; $i++) $this->event($noisy, $noisyPage, 'click', '2026-10-12 19:01:00', ['label' => 'Click']);
        $unconfirmed = $this->event($noisy, $noisyPage, 'favorite_changed', '2026-10-12 19:01:00', ['result' => true]);
        DB::table('activity_events')->where('id', $unconfirmed)->update(['source' => 'browser']);
        $withoutPage = $this->visit($user, '2026-10-12 18:00:00');
        $this->event($withoutPage, null, 'page_view', '2026-10-12 18:00:00', [], $piece);
        $this->event($withoutPage, null, 'media_progress', '2026-10-12 18:00:10', ['media_kind' => 'video']);
        $empty = $this->visit($user, '2026-10-12 20:00:00');
        $otherUser = $this->account(); $otherVisit = $this->visit($otherUser); $this->page($otherVisit);
        foreach (['most_actions' => [$rich, $noisy, $withoutPage, $empty], 'fewest_actions' => [$empty, $noisy, $withoutPage, $rich]] as $value => $expected) {
            $sort = new VisitSort(\Illuminate\Http\Request::create('/', 'GET', ['visit_sort' => $value]));
            DB::enableQueryLog(); DB::flushQueryLog();
            $visits = (new Queries)->visitList(null, $user->id, 25, $sort);
            $queryCount = count(DB::getQueryLog()); DB::disableQueryLog();
            $this->assertSame($expected, $visits->pluck('id')->all());
            $this->assertSame(4, $visits->total());
            $this->assertLessThanOrEqual(5, $queryCount);
            foreach ($visits as $visit) $this->assertSame(count($visit->summary_parts), (int) $visit->actions_count, 'The SQL ranking exactly matches the displayed tags.');
            $this->assertSame(14, (int) $visits->firstWhere('id', $rich)->actions_count);
            $this->assertSame('4 pieces', $visits->firstWhere('id', $rich)->summary_parts[0]['label']);
            $this->assertSame(array_slice($expected, 0, 2), (new Queries)->visitList(null, $user->id, 2, $sort)->pluck('id')->all());
        }
    }

    public function test_visit_sort_controls_preserve_filters_reset_pagination_and_validate_choices()
    {
        $user = $this->account();
        for ($i = 0; $i < 28; $i++) { $id = $this->visit($user, carbon('2026-10-12 10:00:00')->addMinutes($i)->toDateTimeString()); $this->page($id); }
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users', ['visit_sort' => 'pages', 'visit_order' => 'desc', 'visits_page' => 2, 'range' => '7', 'platform' => 'webapp', 'audience' => 'registered', 'history' => 1]))->assertOk()->assertSee('Visits with the most pages first.')
            ->assertViewHas('visitSort', function ($sort) { return $sort->value === 'most_pages'; });
        $document = new \DOMDocument; @$document->loadHTML($response->getContent()); $xpath = new \DOMXPath($document);
        $form = $xpath->query('//form[@aria-label="Visit sorting"]')->item(0);
        $this->assertSame(1, $xpath->query('.//option[@value="pages" and @selected]', $form)->length);
        $this->assertSame(4, $xpath->query('.//option', $form)->length);
        $this->assertSame(1, $xpath->query('.//button[@name="visit_order" and @value="desc" and not(contains(@class,"ua-order-toggle"))]', $form)->length);
        $this->assertSame(1, $xpath->query('.//button[@name="visit_order" and @value="asc" and contains(@class,"ua-order-toggle")]', $form)->length);
        foreach (['range' => '7', 'platform' => 'webapp', 'audience' => 'registered', 'history' => '1'] as $name => $value) $this->assertSame($value, $xpath->query('.//input[@name="'.$name.'"]', $form)->item(0)->getAttribute('value'));
        $this->assertSame(0, $xpath->query('.//input[@name="visits_page"]', $form)->length);
        $this->assertStringNotContainsString('?', $form->getAttribute('action'));
        $this->assertSame(1, $xpath->query('//form[@aria-label="Activity filters"]/input[@name="visit_sort" and @value="pages"]')->length);
        $this->assertSame(1, $xpath->query('//form[@aria-label="Activity filters"]/input[@name="visit_order" and @value="desc"]')->length);
        $this->assertSame(0, $xpath->query('//h3[@class="ua-day"]')->length, 'Metric sorts are not regrouped by day.');
        $this->assertGreaterThan(0, $xpath->query('//div[@class="ua-visit-copy"]/p[contains(text(), "Oct 12, 2026")]')->length);
        $this->assertGreaterThan(0, $xpath->query('//a[contains(@href, "visits_page=1") and contains(@href, "visit_sort=pages") and contains(@href, "visit_order=desc") and contains(@href, "platform=webapp")]')->length);
        $this->get(route('admin.analytics.users'))->assertOk()->assertSee('Newest visits first.')->assertSee('class="ua-day"', false);
        foreach (['overview', 'visits'] as $tab) $this->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => $tab, 'visit_sort' => 'most_actions']))->assertOk()->assertSee('Actions count the colored tags.')->assertSee('1 action');
        $this->withExceptionHandling();
        foreach (['password', 'longest desc;drop table users', ['most_pages']] as $value) $this->getJson(route('admin.analytics.users', ['visit_sort' => $value]))->assertStatus(422);
        $this->getJson(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits', 'visit_sort' => 'bad']))->assertStatus(422);
        foreach (['up', ['asc']] as $value) $this->getJson(route('admin.analytics.users', ['visit_sort' => 'pages', 'visit_order' => $value]))->assertStatus(422);
    }

    public function test_visit_sort_field_and_order_can_be_selected_independently()
    {
        $user = $this->account();
        $first = $this->visit($user, '2026-10-12 10:00:00'); $this->page($first);
        $second = $this->visit($user, '2026-10-12 19:00:00'); for ($i = 0; $i < 3; $i++) $this->page($second);
        $this->actingAs(create(Admin::class), 'admin');
        foreach (['desc' => [$second, $first], 'asc' => [$first, $second]] as $order => $expected) {
            $response = $this->get(route('admin.analytics.users', ['visit_sort' => 'pages', 'visit_order' => $order, 'platform' => 'webapp']))->assertOk()
                ->assertViewHas('visits', function ($visits) use ($expected) { return $visits->pluck('id')->all() === $expected; });
            $document = new \DOMDocument; @$document->loadHTML($response->getContent()); $xpath = new \DOMXPath($document);
            $form = $xpath->query('//form[@aria-label="Visit sorting"]')->item(0);
            $labels = []; foreach ($xpath->query('.//option', $form) as $option) $labels[] = $option->textContent;
            $this->assertSame(['Date/time', 'Duration', 'Pages', 'Actions'], $labels);
            $buttons = $xpath->query('.//button[@name="visit_order"]', $form);
            $this->assertSame($order, $buttons[0]->getAttribute('value'), 'Apply retains the current direction.');
            $this->assertSame($order === 'asc' ? 'desc' : 'asc', $buttons[1]->getAttribute('value'), 'The separate button reverses direction.');
            $this->assertStringContainsString('Reverse order', $buttons[1]->textContent);
            $this->assertSame(0, $xpath->query('.//input[@name="visit_order"]', $form)->length, 'Only the clicked button submits an order; there is no conflicting hidden value.');
        }
        foreach (['date' => ['desc' => 'newest', 'asc' => 'oldest'], 'duration' => ['desc' => 'longest', 'asc' => 'shortest'], 'pages' => ['desc' => 'most_pages', 'asc' => 'fewest_pages'], 'actions' => ['desc' => 'most_actions', 'asc' => 'fewest_actions']] as $field => $orders) {
            foreach ($orders as $order => $legacy) {
                $sort = new VisitSort(\Illuminate\Http\Request::create('/', 'GET', ['visit_sort' => $field, 'visit_order' => $order]));
                $old = new VisitSort(\Illuminate\Http\Request::create('/', 'GET', ['visit_sort' => $legacy]));
                $this->assertSame($legacy, $sort->value);
                $this->assertSame($field, $sort->field);
                $this->assertSame($order, $sort->order);
                $this->assertSame($old->value, $sort->value, 'Existing combined-sort bookmarks retain their order.');
            }
        }
    }

    public function test_visit_summaries_distinguish_recorded_audio_video_and_unknown_media_with_icons()
    {
        $user = $this->account();
        $audioVisit = $this->visit($user); $audioPage = $this->page($audioVisit);
        $this->event($audioVisit, $audioPage, 'media_play', '2026-10-12 19:15:00', ['media_kind' => 'audio']);
        $videoVisit = $this->visit($user); $videoPage = $this->page($videoVisit);
        $this->event($videoVisit, $videoPage, 'media_play', '2026-10-12 19:15:00', ['media_kind' => 'video']);
        $mixedVisit = $this->visit($user); $mixedPage = $this->page($mixedVisit);
        foreach (['audio', 'audio', 'video', null, '<script>video</script>'] as $kind) {
            $this->event($mixedVisit, $mixedPage, 'media_play', '2026-10-12 19:15:00', ['media_kind' => $kind]);
        }
        $piece = Model::withoutEvents(function () { return create(Piece::class); });
        $this->event($mixedVisit, $mixedPage, 'page_view', '2026-10-12 19:14:00', [], $piece);
        foreach (['score_opened', 'annotation_saved', 'folder_created', 'trial_started', 'subscription_paid', 'premium_opened'] as $type) $this->event($mixedVisit, $mixedPage, $type);
        $this->event($mixedVisit, $mixedPage, 'favorite_changed', '2026-10-12 19:16:00', ['result' => true]);
        $emptyVisit = $this->visit($user);
        DB::enableQueryLog(); DB::flushQueryLog();
        $visits = (new Queries)->visitList($this->filters())->getCollection()->keyBy('id');
        $queryCount = count(DB::getQueryLog()); DB::disableQueryLog();
        $this->assertLessThanOrEqual(5, $queryCount, 'Media kinds are aggregated in the existing bounded summary query.');
        $this->assertSame(['audio'], $visits[$audioVisit]->media_kinds);
        $this->assertSame(['video'], $visits[$videoVisit]->media_kinds);
        $this->assertSame(['audio', 'video', 'unknown'], $visits[$mixedVisit]->media_kinds);
        $this->assertSame(5, (int) $visits[$mixedVisit]->counts['media_play']);
        $this->assertSame(['music', 'volume-2', 'video', 'circle-play', 'book-open', 'pencil', 'folder-plus', 'gift', 'credit-card', 'crown', 'heart'], array_column($visits[$mixedVisit]->summary_parts, 'icon'));
        $this->assertSame('1 page · Audio', $visits[$audioVisit]->summary);
        $this->assertSame('1 page · Video', $visits[$videoVisit]->summary);
        $this->assertSame([], $visits[$emptyVisit]->summary_parts);
        $this->assertSame('Limited activity details were recorded for this visit.', $visits[$emptyVisit]->summary);
        $this->actingAs(create(Admin::class), 'admin');
        foreach (['admin.analytics.users' => [], 'admin.analytics.users.profile' => ['user' => $user->id, 'tab' => 'visits'], 'admin.analytics.users.journey' => ['visit' => $mixedVisit]] as $route => $parameters) {
            $response = $this->get(route($route, $parameters))->assertOk()->assertDontSee('&lt;script&gt;video');
            $document = new \DOMDocument; @$document->loadHTML($response->getContent()); $xpath = new \DOMXPath($document);
            $summary = $xpath->query('//ul[@class="ua-summary-items"][.//svg[@data-lucide-name="music"]]')->item(0);
            $this->assertStringContainsString('1 piece', $summary->textContent);
            $this->assertStringContainsString('Audio', $summary->textContent);
            $this->assertStringContainsString('Video', $summary->textContent);
            $this->assertStringContainsString('Premium', $summary->textContent);
            $this->assertStringNotContainsString('Opened', $summary->textContent);
            $this->assertStringNotContainsString('Played', $summary->textContent);
            $this->assertStringNotContainsString('Viewed', $summary->textContent);
            foreach (['music', 'volume-2', 'video', 'circle-play', 'crown', 'heart'] as $icon) {
                $this->assertGreaterThan(0, $xpath->query('//ul[@class="ua-summary-items"]//svg[@data-lucide-name="'.$icon.'" and @aria-hidden="true"]')->length);
            }
        }
        $this->assertDatabaseCount('activity_events', 15);
    }

    public function test_journey_page_and_action_payloads_are_paginated_and_bounded()
    {
        $visit = $this->visit();
        for ($i = 0; $i < 24; $i++) $this->page($visit, 'webapp.discover', carbon('2026-10-12 10:00:00')->addMinutes($i)->toDateTimeString());
        $data = (new Journey)->get($visit);
        $this->assertCount(12, $data['pages']);
        $this->assertSame(24, $data['pages']->total());
        $this->assertSame(24, $data['visit']->pages_count);
    }

    public function test_people_search_filters_and_sort_use_existing_account_data()
    {
        $user = $this->account(['first_name' => 'Lena', 'email' => 'lena@example.test', 'origin' => 'ios', 'super_user' => true]);
        $this->account(['first_name' => 'Other']);
        $this->visit($user); $this->visit($user);
        $request = \Illuminate\Http\Request::create('/', 'GET', ['search' => 'lena@', 'origin' => 'ios', 'status' => 'Super user', 'activity' => 'repeat', 'sort' => 'engagement']);
        $people = (new People)->directory($request);
        $this->assertSame([$user->id], $people->pluck('id')->all());
        $this->assertSame(2, (int) $people[0]->visits_count);
        $endOnly = \Illuminate\Http\Request::create('/', 'GET', ['active_to' => '2026-10-12', 'registered_to' => '2026-10-12']);
        $this->assertSame(1, (new People)->directory($endOnly)->total());
    }

    public function test_non_admins_cannot_read_any_analytics_surface_and_filters_are_validated()
    {
        $user = $this->account(); $visit = $this->visit($user);
        $this->withExceptionHandling()->actingAs($user, 'web');
        foreach ([route('admin.analytics.users'), route('admin.analytics.users.people'), route('admin.analytics.users.insights'), route('admin.analytics.users.profile', $user->id), route('admin.analytics.users.journey', $visit)] as $url) $this->get($url)->assertRedirect();
        $this->actingAs(create(Admin::class), 'admin');
        $this->getJson(route('admin.analytics.users', ['range' => 'custom', 'from' => '2020-01-01', 'to' => '2026-10-12']))->assertStatus(422);
        $this->getJson(route('admin.analytics.users.people', ['sort' => 'password']))->assertStatus(422);
        $this->getJson(route('admin.analytics.users', ['platform' => 'forged']))->assertStatus(422);
    }

    public function test_passive_foreground_checkpoints_do_not_keep_an_idle_visit_alive()
    {
        $this->get('/review/journey')->assertOk(); $page = DB::table('activity_pages')->first();
        $this->travel(31)->minutes();
        $this->postJson('/activity/events', ['page_token' => Crypt::encryptString($page->id), 'events' => [['id' => (string) Str::uuid(), 'type' => 'page_checkpoint', 'elapsed_ms' => 1860000, 'sequence' => 1, 'metadata' => ['visible_ms' => 1860000]]]])->assertNoContent();
        $this->get('/review/journey')->assertOk();
        $this->assertDatabaseCount('activity_visits', 2);
    }

    public function test_retry_cannot_change_page_timing_or_identity_and_new_utc_fields_preserve_old_times()
    {
        $this->get('/review/journey')->assertOk(); $page = DB::table('activity_pages')->first();
        $this->travel(20)->seconds();
        $event = ['id' => (string) Str::uuid(), 'type' => 'page_checkpoint', 'elapsed_ms' => 1000, 'sequence' => 1, 'metadata' => ['visible_ms' => 1000]];
        $this->postJson('/activity/events', ['page_token' => Crypt::encryptString($page->id), 'events' => [$event]])->assertNoContent();
        $event['elapsed_ms'] = 20000; $event['metadata']['visible_ms'] = 20000;
        $this->postJson('/activity/events', ['page_token' => Crypt::encryptString($page->id), 'events' => [$event]])->assertNoContent();
        $this->assertSame(1000, (int) DB::table('activity_pages')->value('visible_ms'));
        $stored = DB::table('activity_events')->where('event_id', $event['id'])->first();
        $this->assertSame('2026-10-12 19:30:01.000', $stored->occurred_at);
        $this->assertSame('2026-10-12 23:30:01.000', $stored->occurred_at_utc);
    }

    public function test_utc_offsets_and_inactivity_boundaries_survive_the_repeated_daylight_saving_hour()
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-01T05:55:00Z'));
        $this->get('/review/journey')->assertOk(); $page = DB::table('activity_pages')->first();
        $this->assertSame('2026-11-01 05:55:00.000', DB::table('activity_visits')->value('last_interaction_at_utc'));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-01T06:15:00Z'));
        $id = (string) Str::uuid();
        $this->postJson('/activity/events', ['page_token' => Crypt::encryptString($page->id), 'events' => [['id' => $id, 'type' => 'page_checkpoint', 'elapsed_ms' => 1200000, 'sequence' => 1]]])->assertNoContent();
        $stored = DB::table('activity_events')->where('event_id', $id)->first();
        $this->assertSame('2026-11-01 01:15:00.000', $stored->occurred_at);
        $this->assertSame('2026-11-01 06:15:00.000', $stored->occurred_at_utc);
        $this->assertSame('2026-11-01 06:15:00.000', DB::table('activity_visits')->value('last_activity_at_utc'));
        $this->assertSame('2026-11-01 05:55:00.000', DB::table('activity_visits')->value('last_interaction_at_utc'));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-01T06:31:00Z'));
        $this->get('/review/journey')->assertOk();
        $this->assertDatabaseCount('activity_visits', 2);
    }

    public function test_confirmed_invoice_is_idempotent_ungrouped_and_has_no_payment_payload()
    {
        $user = $this->account();
        $source = DB::table('stripe_memberships')->insertGetId(['stripe_id' => 'cus_fixture', 'plan' => 'monthly', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('memberships')->insert(['user_id' => $user->id, 'source_type' => \App\Billing\Sources\Stripe::class, 'source_id' => $source, 'created_at' => now(), 'updated_at' => now()]);
        $payload = ['created' => now()->timestamp, 'data' => ['object' => ['id' => 'in_fixture', 'subscription' => 'sub_fixture', 'customer' => 'cus_fixture', 'paid' => true, 'amount_paid' => 900, 'card' => 'private-card', 'email' => 'private@example.test']]];
        Actions::paidInvoice($payload); Actions::paidInvoice($payload);
        $this->assertDatabaseCount('activity_events', 1);
        $this->assertDatabaseHas('activity_events', ['user_id' => $user->id, 'type' => 'subscription_paid', 'visit_id' => null]);
        $this->assertSame('{"status":"paid"}', DB::table('activity_events')->value('metadata'));
        $this->actingAs(create(Admin::class), 'admin')->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'account']))->assertOk()->assertSee('Subscription payment confirmed');
    }

    public function test_saved_state_is_not_reconstructed_as_historical_favorite_actions()
    {
        $user = $this->account();
        $piece = Model::withoutEvents(function () { return create(Piece::class); });
        DB::table('favorites')->insert(['user_id' => $user->id, 'piece_id' => $piece->id, 'created_at' => now(), 'updated_at' => now()]);
        $data = (new People)->profile($user, 'overview', $this->filters());
        $this->assertSame(1, $data['stats']->favorites);
        $this->assertSame(0, (int) $data['stats']->visits);
        $this->assertFalse($data['engagement']->has('favorite_changed'));
        $insights = (new Insights)->get($this->filters());
        $this->assertNull($insights['engagement']['meaningful_playback']);
        $this->assertNull($insights['engagement']['synthesia']);
        $this->assertNull($insights['engagement']['favorites_added']);
    }

    public function test_successful_folder_and_annotation_changes_record_confirmed_counts_without_private_contents()
    {
        $this->withoutMiddleware([\App\Http\Middleware\UpdateLocation::class]);
        $user = $this->account(['super_user' => true]);
        $piece = Model::withoutEvents(function () { return create(Piece::class, ['score_path' => 'scores/analytics.pdf', 'score_url' => null]); });
        $this->actingAs($user, 'web')->get('http://my.'.config('app.short_url').'/review/journey')->assertOk();
        $this->postJson(route('webapp.users.favorites.folders.store'), ['name' => 'Practice', 'piece_id' => $piece->id])->assertOk();
        $folder = DB::table('favorite_folders')->where('user_id', $user->id)->first();
        $this->patch(route('webapp.users.favorites.folders.update', $folder->id), ['name' => 'Repertoire', 'description' => 'private-folder-description'])->assertRedirect();
        $this->assertDatabaseHas('activity_events', ['type' => 'folder_created', 'resource_id' => $folder->id, 'source' => 'server']);
        $this->assertDatabaseHas('activity_events', ['type' => 'folder_renamed', 'resource_id' => $folder->id, 'source' => 'server']);
        $this->assertDatabaseHas('activity_events', ['type' => 'favorite_changed', 'resource_id' => $piece->id, 'source' => 'server']);
        $this->assertLessThan(DB::table('activity_events')->where('type', 'favorite_changed')->value('id'), DB::table('activity_events')->where('type', 'folder_created')->value('id'));
        $url = route('webapp.pieces.score.annotations.update', $piece->id);
        $payload = ['score' => hash('sha256', $piece->score_path), 'fingerprint' => str_repeat('a', 32), 'revision' => 0,
            'marks' => [['id' => 'one', 'type' => 'text', 'page' => 1, 'color' => '#1565c0', 'x' => .2, 'y' => .3, 'size' => .03, 'text' => 'private-score-text']]];
        $this->putJson($url, $payload)->assertExactJson(['revision' => 1]);
        $this->putJson($url, $payload)->assertExactJson(['revision' => 1]);
        $payload['revision'] = 1; $payload['marks'][0]['text'] = 'private-edited-text';
        $this->putJson($url, $payload)->assertExactJson(['revision' => 2]);
        $payload['revision'] = 2; $payload['marks'] = [];
        $this->putJson($url, $payload)->assertExactJson(['revision' => 3]);
        $events = DB::table('activity_events')->where('type', 'annotation_saved')->orderBy('id')->get();
        $this->assertCount(3, $events);
        $this->assertSame(1, json_decode($events[0]->metadata, true)['created_count']);
        $this->assertSame(1, json_decode($events[1]->metadata, true)['edited_count']);
        $this->assertSame(1, json_decode($events[2]->metadata, true)['removed_count']);
        $this->assertStringNotContainsString('private-', DB::table('activity_events')->pluck('metadata')->implode(' '));
        $this->delete(route('webapp.users.favorites.folders.delete', $folder->id))->assertRedirect();
        $this->assertDatabaseHas('activity_events', ['type' => 'folder_deleted', 'resource_id' => $folder->id, 'source' => 'server']);
    }

    public function test_conversion_steps_require_ordered_identity_and_confirmed_billing_outcomes()
    {
        $user = $this->account(); $other = $this->account();
        $visit = $this->visit($user); $page = $this->page($visit);
        $otherVisit = $this->visit($other);
        $this->event($visit, $page, 'premium_restriction', '2026-10-12 19:14:01');
        $this->event($visit, $page, 'premium_opened', '2026-10-12 19:14:02');
        $trial = $this->event($visit, $page, 'trial_started', '2026-10-12 19:14:03');
        DB::table('activity_events')->where('id', $trial)->update(['source' => 'server']);
        $this->event($visit, $page, 'subscription_paid', '2026-10-12 19:14:04'); // Forged client outcome must not count.
        $foreign = $this->event($otherVisit, null, 'subscription_paid', '2026-10-12 19:14:04');
        DB::table('activity_events')->where('id', $foreign)->update(['source' => 'server']);
        $first = collect((new Insights)->get($this->filters())['conversions'])->keyBy('from');
        $this->assertSame(1, (int) $first['premium_restriction']['completed']);
        $this->assertSame(0, (int) $first['trial_started']['completed']);
        $paid = $this->event($visit, null, 'subscription_paid', '2026-10-12 19:15:00');
        DB::table('activity_events')->where('id', $paid)->update(['source' => 'server', 'visit_id' => null]);
        $second = collect((new Insights)->get($this->filters())['conversions'])->keyBy('from');
        $this->assertSame(1, (int) $second['trial_started']['completed']);
        $this->assertSame(1, (int) $second['premium_opened']['completed']);
    }

    public function test_purge_also_removes_activity_reliably_linked_from_anonymous_visits()
    {
        $user = $this->account(); $visit = $this->visit(); $page = $this->page($visit);
        $this->event($visit, $page, 'click');
        DB::table('activity_visit_links')->insert(['visit_id' => $visit, 'user_id' => $user->id, 'linked_at_utc' => now('UTC'), 'reason' => 'login']);
        $other = $this->visit();
        $this->actingAs(create(Admin::class), 'admin')->delete(route('admin.users.purge', $user->id))->assertRedirect();
        $this->assertDatabaseMissing('activity_visits', ['id' => $visit]);
        $this->assertDatabaseMissing('activity_pages', ['id' => $page]);
        $this->assertDatabaseMissing('activity_events', ['visit_id' => $visit]);
        $this->assertDatabaseHas('activity_visits', ['id' => $other]);
    }

    public function test_browser_accepts_additive_music_events_but_cannot_claim_server_account_outcomes()
    {
        $this->withExceptionHandling();
        $this->get('/review/journey')->assertOk(); $page = DB::table('activity_pages')->first();
        $this->travel(2)->seconds();
        $types = ['media_rate', 'score_opened', 'score_progress', 'score_page', 'premium_restriction', 'premium_opened', 'registration_started', 'match_completed'];
        $events = [];
        foreach ($types as $index => $type) $events[] = ['id' => (string) Str::uuid(), 'type' => $type, 'elapsed_ms' => 1000, 'sequence' => $index + 1, 'metadata' => ['playback_id' => (string) Str::uuid(), 'unique_watched_percent' => 20, 'unique_watched_seconds' => 12]];
        $token = Crypt::encryptString($page->id);
        $this->postJson('/activity/events', ['page_token' => $token, 'events' => $events])->assertNoContent();
        foreach ($types as $type) $this->assertDatabaseHas('activity_events', ['type' => $type, 'source' => 'browser', 'page_id' => $page->id]);
        foreach (['subscription_paid', 'registration_completed', 'annotation_saved', 'favorite_changed'] as $type) {
            $events[0]['id'] = (string) Str::uuid(); $events[0]['type'] = $type;
            $this->postJson('/activity/events', ['page_token' => $token, 'events' => [$events[0]]])->assertStatus(422);
            $this->assertDatabaseMissing('activity_events', ['type' => $type]);
        }
    }

    public function test_analytics_migration_upgrade_and_rollback_preserve_original_history()
    {
        $visit = $this->visit(); $page = $this->page($visit);
        $event = $this->event($visit, $page, 'page_view');
        $migration = new \ExtendActivityForUserAnalytics;
        $migration->down();
        $this->assertDatabaseHas('activity_visits', ['id' => $visit]);
        $this->assertDatabaseHas('activity_pages', ['id' => $page]);
        $this->assertDatabaseHas('activity_events', ['id' => $event]);
        $this->assertFalse(\Schema::hasColumn('activity_events', 'occurred_at_utc'));
        $migration->up();
        $this->assertTrue(\Schema::hasColumn('activity_visits', 'last_interaction_at_utc'));
        $this->assertDatabaseHas('activity_events', ['id' => $event, 'occurred_at_utc' => null]);
    }
}
