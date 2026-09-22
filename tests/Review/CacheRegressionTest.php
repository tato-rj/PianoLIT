<?php

namespace Tests\Review;

use App\Api\Api;
use Illuminate\Support\Facades\{Cache, Redis};

class CacheRegressionTest extends ReviewTestCase
{
    public function test_discover_uses_its_own_fallback_key_and_does_not_cache_personal_rows()
    {
        Redis::shouldReceive('get')->with('app.discover')->twice()->andReturn(null);
        Cache::put('app.discover', collect([['title' => 'Public row']]), 60);
        Cache::put('app.explore', collect([['title' => 'Explore row']]), 60);
        $api = new class extends Api {
            public function suggestions($title) { return ['title' => $title]; }
        };

        $this->assertCount(2, $api->discover());
        $this->assertCount(2, $api->discover());
        $this->assertCount(1, Cache::get('app.discover'));
        $this->assertSame('Explore row', Cache::get('app.explore')->first()['title']);
    }

    public function test_webapp_explore_does_not_mutate_the_shared_cached_rows()
    {
        Redis::shouldReceive('get')->with('app.explore')->twice()->andReturn(null);
        Cache::put('app.explore', collect([['label' => 'Shared']]), 60);
        $this->assertCount(2, (new Api)->for('webapp')->explore());
        $this->assertCount(1, (new Api)->explore());
    }

    public function test_empty_catalogue_can_build_a_free_pick_and_similar_row()
    {
        $api = (new Api)->order(0);
        $this->assertSame([], $api->free('Free weekly pick')['content']);
        $this->assertSame([], $api->similar('Similar', null)['content']);
    }
}
