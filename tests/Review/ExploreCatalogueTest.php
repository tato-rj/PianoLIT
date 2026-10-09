<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag, User};
use App\Services\WebApp\ExploreCatalogue;
use Illuminate\Database\Eloquent\Model;

class ExploreCatalogueTest extends ReviewTestCase
{
    private $tags, $pieces;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->tags = collect();
            foreach (array_reverse(ExploreCatalogue::LEVELS) as $name) {
                $this->tags[$name] = create(Tag::class, ['name' => $name, 'type' => strpos($name, ' ') ? 'sublevel' : 'level']);
            }
            foreach (['agitated' => 'mood', 'crazy' => 'mood', 'happy' => 'mood', 'short' => 'length', 'baroque' => 'period', 'left hand' => 'technique'] as $name => $type) {
                $this->tags[$name] = create(Tag::class, compact('name', 'type'));
            }
            $this->pieces = collect();
            for ($i = 0; $i < 13; $i++) {
                $piece = create(Piece::class, ['name' => 'Guided fixture '.$i, 'is_free' => $i === 0, 'highlighted_at' => now(), 'audio_path' => 'example.mp3']);
                $names = [$i === 12 ? 'advanced' : 'elementary', $i % 2 ? 'crazy' : 'agitated', 'short', 'baroque'];
                if ($i < 2) $names[] = 'left hand';
                $piece->tags()->attach($this->tags->only($names)->pluck('id'));
                $this->pieces->push($piece);
            }
        });
    }

    public function test_directory_renders_real_counts_and_distinct_sections_without_mobile_feed()
    {
        $response = $this->get(route('webapp.explore'))->assertOk()
            ->assertSee('Find your way through the repertoire.')
            ->assertSee('Technique')->assertSee('Periods & Styles', false)
            ->assertDontSee('Playing needs')->assertSee('View all 12 pieces');
        $data = $response->viewData('moods');
        $this->assertSame(12, $data['dramatic']['count']);
        $this->assertNotNull($data['dramatic']['example']);
        $this->assertSame(ExploreCatalogue::LEVELS, $response->viewData('levels')->pluck('name')->all());
        $this->get(route('webapp.explore', ['level' => 'advanced']))->assertOk()->assertSee('View all 1 piece');
        if (getenv('EXPLORE_PREVIEW')) {
            file_put_contents(getenv('EXPLORE_PREVIEW'), $response->getContent());
            file_put_contents(dirname(getenv('EXPLORE_PREVIEW')).'/level.html', $this->get(route('webapp.explore', ['level' => 'elementary']))->getContent());
        }
    }

    public function test_guided_filters_intersect_before_guest_limit_and_members_can_page()
    {
        $params = ['level' => 'elementary', 'mood' => 'dramatic'];
        $this->assertSame(12, app(ExploreCatalogue::class)->query($params)->count());
        $url = ExploreCatalogue::url($params);
        $response = $this->getJson($url)->assertOk()->assertSee('Sign up to see more.');
        $this->assertSame(3, substr_count($response->getContent(), 'data-sort-name='));
        $this->assertSame('', $this->getJson($url.'&page=2')->assertOk()->getContent());
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        $this->assertSame(10, substr_count($this->getJson($url)->assertOk()->getContent(), 'data-sort-name='));
        $this->assertSame(2, substr_count($this->getJson($url.'&page=2')->assertOk()->getContent(), 'data-sort-name='));
        $this->assertSame(2, substr_count($this->getJson(ExploreCatalogue::url($params + ['tag' => $this->tags['left hand']->id]))->assertOk()->getContent(), 'data-sort-name='));
        $this->assertSame(0, substr_count($this->getJson(ExploreCatalogue::url($params + ['filters' => ['["happy"]']]))->assertOk()->getContent(), 'data-sort-name='));
        $this->assertSame(0, app(ExploreCatalogue::class)->query(['tag' => $this->tags['agitated']->id])->count());
        $this->assertSame(11, app(ExploreCatalogue::class)->query($params + ['past' => 1, 'short' => 1])->count());
    }

    public function test_empty_catalogue_remains_browsable()
    {
        \Illuminate\Support\Facades\DB::table('piece_tag')->delete();
        \Illuminate\Support\Facades\DB::table('tags')->delete();
        $this->get(route('webapp.explore'))->assertOk()->assertSee('Levels are being prepared.')
            ->assertSee('Find your next piece')->assertSee('Styles are being prepared.');
        $this->withExceptionHandling()->get(route('webapp.explore', ['level' => 'elementary']))->assertRedirect(route('webapp.discover'));
    }

    public function test_composer_destinations_and_invalid_shapes()
    {
        $woman = $this->pieces->first()->composer;
        Model::withoutEvents(function () use ($woman) { $woman->update(['gender' => 'female']); });
        $this->get(route('webapp.composers.index', ['gender' => 'female']))->assertOk()->assertSee('Women composers')
            ->assertViewHas('composers', function ($composers) { return $composers->every(function ($composer) { return $composer->gender === 'female'; }); });
        $this->get(route('webapp.composers.index', ['country' => $woman->country_id]))->assertOk()
            ->assertViewHas('composers', function ($composers) use ($woman) { return $composers->every(function ($composer) use ($woman) { return $composer->country_id === $woman->country_id; }); });
        $this->withExceptionHandling();
        foreach ([['mood' => 'invalid'], ['level' => ['elementary']], ['tag' => '../x'], ['filters' => ['{"x":"y"}']], ['short' => 'yes']] as $params) {
            $this->getJson(ExploreCatalogue::url($params))->assertStatus(422);
        }
        $this->getJson(route('webapp.explore', ['level' => 'invalid']))->assertStatus(422);
    }
}
