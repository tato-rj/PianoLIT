<?php

namespace Tests\Review;

use App\{Piece, Tag, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WebAppSearchLimitTest extends ReviewTestCase
{
    protected $pieces, $mood;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $tags = collect(['level' => 'elementary', 'period' => 'baroque', 'length' => 'short', 'mood' => 'happy'])
                ->map(function ($name, $type) { return create(Tag::class, compact('name', 'type')); });
            $this->mood = $tags['mood'];
            $this->pieces = collect();
            for ($i = 0; $i < 12; $i++) {
                $piece = create(Piece::class, ['name' => 'Search fixture '.$i, 'created_at' => now()->subMinutes($i)]);
                $piece->tags()->attach($tags->pluck('id'));
                $this->pieces->push($piece);
            }
        });
    }

    public function test_visitors_only_receive_the_first_three_results_with_signup_links()
    {
        foreach ([[], ['lazy-load' => '', 'page' => 1], ['page' => 0], ['count' => 1, 'user_id' => 123]] as $extra) {
            $response = $this->getJson(route('webapp.search.results', array_merge(['search' => 'happy'], $extra)))
                ->assertOk()->assertSee('Sign up to see more.')->assertSee(route('register'), false);
            $this->assertSame(3, substr_count($response->getContent(), 'data-sort-name='));
            foreach ($this->pieces->take(3) as $piece) $response->assertSee($piece->name);
            $response->assertDontSee($this->pieces[3]->name);
            if (getenv('WEBAPP_SEARCH_PREVIEW')) {
                $html = '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/css/app.css"></head><body><div class="container"><main class="col-lg-8 mx-auto"><h3 class="mt-4">Search results</h3>'.$response->getContent().'</main></div></body></html>';
                file_put_contents(getenv('WEBAPP_SEARCH_PREVIEW'), $html);
            }
        }
    }

    public function test_visitors_cannot_reveal_more_results_by_paging()
    {
        foreach ([2, 3, 100] as $page) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $response = $this->getJson(route('webapp.search.results', ['search' => 'happy', 'page' => $page]))->assertOk();
            $this->assertSame('', $response->getContent());
            $this->assertCount(0, DB::getQueryLog());
            DB::disableQueryLog();
        }
    }

    public function test_local_filters_apply_before_the_visitor_limit_and_empty_search_has_no_signup_prompt()
    {
        $tag = Model::withoutEvents(function () { return create(Tag::class, ['name' => 'special', 'type' => 'mood']); });
        foreach ($this->pieces->slice(5, 4) as $piece) $piece->tags()->attach($tag);
        $response = $this->getJson(route('webapp.search.results', ['search' => 'happy', 'filters' => ['["special"]']]))->assertOk();
        $this->assertSame(3, substr_count($response->getContent(), 'data-sort-name='));
        foreach ($this->pieces->slice(5, 3) as $piece) $response->assertSee($piece->name);
        $response->assertDontSee($this->pieces[8]->name);
        $response = $this->getJson(route('webapp.search.results', ['search' => 'happy', 'filters' => ['["missing"]']]))->assertOk();
        $this->assertSame('', trim($response->getContent()));
        $this->getJson(route('webapp.search.results'))->assertOk()->assertDontSee('data-search-signup');
    }

    public function test_free_registered_accounts_keep_all_results_and_pagination()
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        foreach ([[[], 12], [['lazy-load' => '', 'page' => 1], 10], [['lazy-load' => '', 'page' => 2], 2]] as [$extra, $count]) {
            $response = $this->getJson(route('webapp.search.results', array_merge(['search' => 'happy'], $extra)))
                ->assertOk()->assertDontSee('data-search-signup');
            $this->assertSame($count, substr_count($response->getContent(), 'data-sort-name='));
        }
    }

    public function test_mobile_search_keeps_all_results()
    {
        $search = new \App\Api\Search(new \Illuminate\Http\Request(['search' => 'happy', 'model' => Tag::class]));
        $this->assertCount(12, $search->query()->filtered()->get());
    }

    public function test_scout_filters_scan_across_pages_in_relevance_order()
    {
        $tag = Model::withoutEvents(function () { return create(Tag::class, ['name' => 'special', 'type' => 'mood']); });
        foreach ($this->pieces->slice(5, 4) as $piece) $piece->tags()->attach($tag);
        $builder = \Mockery::mock(\Laravel\Scout\Builder::class);
        $builder->shouldReceive('paginate')->once()->with(50, 'page', 1)->andReturn(
            new \Illuminate\Pagination\LengthAwarePaginator(new \Illuminate\Database\Eloquent\Collection($this->pieces->take(6)->all()), 100, 50, 1)
        );
        $builder->shouldReceive('paginate')->once()->with(50, 'page', 2)->andReturn(
            new \Illuminate\Pagination\LengthAwarePaginator(new \Illuminate\Database\Eloquent\Collection($this->pieces->slice(6)->all()), 100, 50, 2)
        );
        $search = new class(new \Illuminate\Http\Request(['search' => 'repertoire', 'filters' => ['["special"]']])) extends \App\Api\Search {
            public function useBuilder($builder) { $this->query = $builder; return $this; }
        };
        $pieces = $search->useBuilder($builder)->filtered()->forWebApp();
        $this->assertSame($this->pieces->slice(5, 3)->pluck('id')->all(), $pieces->pluck('id')->all());
    }
}
