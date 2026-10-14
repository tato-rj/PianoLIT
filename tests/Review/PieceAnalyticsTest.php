<?php

namespace Tests\Review;

use App\{Admin, Composer, Country, Piece, Tag, User};
use App\Analytics\PieceReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PieceAnalyticsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-13 14:00:00'));
    }

    protected function piece(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(Piece::class, $attributes); });
    }

    protected function tag($piece, $type, $name)
    {
        $tag = Model::withoutEvents(function () use ($type, $name) { return create(Tag::class, compact('type', 'name')); });
        $piece->tags()->attach($tag->id);
    }

    protected function report(array $filters = [])
    {
        return app(PieceReport::class)->get(Request::create('/', 'GET', $filters));
    }

    public function test_empty_catalogue_renders_complete_charts_without_invented_counts()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.pieces'))->assertOk()
            ->assertSee('Catalogue growth')->assertSee('Media coverage')->assertSee('Levels across periods')
            ->assertSee('Pieces by composer country')->assertSee('Most represented composers')
            ->assertSee('No views have been recorded')->assertSee('No favorites have been recorded')
            ->assertDontSee('quickchart.setup')->assertDontSee('www.gstatic.com');
        $report = $response->viewData('report');
        $this->assertSame(0, $report['summary']['total']);
        $this->assertSame(array_fill(0, 12, 0), array_column($report['growth'], 'total'));
        $this->assertSame(0, $report['countries']['unlocated']);
        $this->assertNull($report['countries']['top']);
        $this->assertSame([0.0, 0.0, 0.0, 0.0], array_column($report['media'], 'share'));
    }

    public function test_media_and_classification_counts_do_not_multiply_piece_rows()
    {
        $first = $this->piece(['audio_path' => 'audio/full.mp3', 'score_path' => 'score.pdf']);
        $second = $this->piece(['audio_path' => '  ', 'audio_path_rh' => 'rh.mp3', 'score_url' => 'https://example.test/score']);
        $third = $this->piece(['score_path' => '', 'score_url' => '  ']);
        $fourth = $this->piece(['composer_id' => 999999]);
        foreach (['beginner', ' Beginner '] as $name) $this->tag($first, 'level', $name);
        $this->tag($first, 'sublevel', 'late beginner');
        $this->tag($first, 'period', 'romantic');
        $this->tag($first, 'period', ' Romantic ');
        $this->tag($first, 'length', 'short');
        $this->tag($second, 'level', 'beginner'); $this->tag($second, 'level', 'advanced');
        $this->tag($second, 'period', 'romantic'); $this->tag($second, 'period', 'classical');
        $this->tag($third, 'sublevel', 'early beginner');
        $this->tag($third, 'genre', 'romantic'); // Matching names in unrelated tag types must not classify a piece.
        $this->tag($fourth, 'level', '  ');
        foreach (['one', 'two'] as $name) DB::table('tutorials')->insert(['piece_id' => $first->id, 'type' => 'Performance', 'category' => 'performance', 'filename' => $name, 'video_url' => 'https://example.test/'.$name]);
        DB::table('tutorials')->insert(['piece_id' => $second->id, 'type' => 'Tutorial', 'category' => 'tutorial', 'filename' => 'empty', 'video_url' => '  ']);
        $report = $this->report();
        $this->assertSame(4, $report['summary']['total']);
        $this->assertSame(1, $report['summary']['audio']);
        $this->assertSame(2, $report['summary']['score']);
        $this->assertSame(1, $report['summary']['video']);
        $this->assertSame(1, $report['summary']['complete']);
        $this->assertSame(1, $report['summary']['missing_composer']);
        foreach (['levels', 'periods', 'lengths', 'genders'] as $key) $this->assertSame(4, array_sum(array_column($report[$key], 'total')));
        $levels = array_column($report['levels'], null, 'key');
        $this->assertSame(1, $levels['beginner']['total']);
        $this->assertSame(1, $levels['beginner']['complete']);
        $this->assertSame(1, $levels['__multiple']['total']);
        $this->assertSame(2, $levels['__missing']['total']);
        $this->assertSame(1, $report['matrix']['cells']['beginner']['romantic']);
        $this->assertSame(1, $report['matrix']['cells']['__multiple']['__multiple']);
        $this->assertSame(4, array_sum(array_map('array_sum', $report['matrix']['cells'])));
        $this->assertSame([50.0, 25.0, 25.0, 25.0], array_column($report['media'], 'share'));
    }

    public function test_growth_has_zero_months_baseline_and_exact_date_boundaries()
    {
        foreach (['2024-01-01 00:00:00', '2025-11-01 00:00:00', '2026-01-31 23:59:59', '2026-10-13 14:00:00', '2026-10-13 14:00:01'] as $date) $this->piece(['created_at' => $date]);
        $undated = $this->piece(); DB::table('pieces')->where('id', $undated->id)->update(['created_at' => null]);
        $report = $this->report();
        $this->assertSame(6, $report['summary']['total']);
        $this->assertSame('2025-11', $report['growth'][0]['month']);
        $this->assertSame(2, $report['growth'][0]['total']);
        $this->assertSame(0, $report['growth'][1]['added']);
        $this->assertSame(1, $report['growth'][2]['added']);
        $this->assertSame(1, $report['growth'][11]['added']);
        $this->assertSame(4, $report['growth'][11]['total']);
        foreach ([24, 36] as $months) {
            $longer = $this->report(['months' => $months]);
            $this->assertCount($months, $longer['growth']);
            $this->assertSame($report['summary'], $longer['summary']);
            $this->assertSame($report['levels'], $longer['levels']);
            $this->assertSame(4, end($longer['growth'])['total']);
        }
    }

    public function test_country_and_gender_counts_weight_pieces_and_retain_missing_metadata()
    {
        $us = Model::withoutEvents(function () { return create(Country::class, ['name' => 'United States', 'flag_code' => ' us ']); });
        $territory = Model::withoutEvents(function () { return create(Country::class, ['name' => 'French Guiana', 'flag_code' => 'GF']); });
        $composer = Model::withoutEvents(function () use ($us) { return create(Composer::class, ['country_id' => $us->id, 'gender' => ' Female ']); });
        $other = Model::withoutEvents(function () use ($territory) { return create(Composer::class, ['country_id' => $territory->id, 'gender' => 'male']); });
        $this->piece(['composer_id' => $composer->id]); $this->piece(['composer_id' => $composer->id]);
        $this->piece(['composer_id' => $other->id]); $this->piece(['composer_id' => 999999]);
        $report = $this->report();
        $this->assertSame(2, $report['summary']['composers']);
        $this->assertSame(2, $report['composers'][0]['total']);
        $this->assertSame(50.0, $report['composers'][0]['share']);
        $this->assertSame(3, $report['countries']['located']);
        $this->assertSame(1, $report['countries']['unlocated']);
        $this->assertSame(2, $report['countries']['byCode']['US']['total']);
        $this->assertSame(1, $report['countries']['byCode']['GF']['total']);
        $this->assertArrayNotHasKey('FR', $report['countries']['byCode']);
        $genders = array_column($report['genders'], null, 'key');
        $this->assertSame(2, $genders['female']['total']);
        $this->assertSame(1, $genders['__missing']['total']);
    }

    public function test_engagement_excludes_required_optouts_and_orphan_piece_rows_with_stable_limits()
    {
        $users = Model::withoutEvents(function () { return [create(User::class, ['email' => 'normal@example.test']), create(User::class, ['email' => ' ARTHURVILLAR@gmail.com ']), create(User::class, ['email' => 'elena.dare@gmail.com'])]; });
        $pieces = [];
        for ($index = 0; $index < 10; $index++) {
            $pieces[] = $piece = $this->piece();
            foreach ($users as $user) {
                foreach (['favorites', 'piece_views'] as $table) DB::table($table)->insert(['piece_id' => $piece->id, 'user_id' => $user->id]);
            }
        }
        foreach (['favorites', 'piece_views'] as $table) DB::table($table)->insert(['piece_id' => 999999, 'user_id' => $users[0]->id]);
        $report = $this->report();
        foreach (['favorites', 'views'] as $key) {
            $this->assertCount(8, $report[$key]);
            $this->assertSame(array_fill(0, 8, 1), array_column($report[$key], 'total'));
            $this->assertEquals(array_slice(array_column($pieces, 'id'), 0, 8), array_column($report[$key], 'id'));
        }
    }

    public function test_report_queries_are_bounded_and_render_escaped_names_with_the_existing_admin_route()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $piece = $this->piece(['name' => '<script>unsafe piece</script>']);
        $this->tag($piece, 'period', '<script>unsafe period</script>');
        DB::table('piece_views')->insert(['piece_id' => $piece->id, 'user_id' => 987654]);
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->report(); $small = DB::getQueryLog(); DB::disableQueryLog();
        for ($index = 0; $index < 15; $index++) $this->piece();
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->report(); $large = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertCount(12, $small);
        $this->assertCount(count($small), $large);
        $this->assertStringNotContainsString('pieces.*', implode(' ', array_column($large, 'query')));
        $this->get(route('admin.analytics.pieces'))->assertOk()->assertSee('&lt;script&gt;unsafe piece&lt;/script&gt;', false)->assertDontSee('<script>unsafe', false);
        $this->get(route('admin.analytics.pieces', ['type' => 'level']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonStructure(['labels', 'datasets' => [['title', 'records', 'colors']]]);
        $this->withExceptionHandling();
        foreach ([[12], 0, 13, 100000, 'invalid'] as $value) $this->getJson(route('admin.analytics.pieces', ['months' => $value]))->assertStatus(422);
        auth('admin')->logout();
        $this->get(route('admin.analytics.pieces'))->assertRedirect(route('admin.login.show'));
    }
}
