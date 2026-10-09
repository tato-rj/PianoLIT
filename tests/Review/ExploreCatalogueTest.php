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

    public function test_mood_artwork_comes_from_matching_pieces_and_is_shared_by_both_columns()
    {
        $covers = [];
        foreach ($this->pieces as $index => $piece) {
            $path = 'pieces/mood-fixture-'.$index.'.jpg';
            Piece::whereKey($piece->id)->update(['cover_path' => $path]);
            $covers[] = $path;
        }
        $this->pieces[0]->tags()->attach($this->tags['happy']);
        $response = $this->get(route('webapp.explore', ['level' => 'elementary']))->assertOk();
        $covers = array_map('storage', $covers);
        $moods = $response->viewData('moods');
        $this->assertContains($moods['dramatic']['image'], $covers);
        $this->assertSame($covers[0], $moods['playful']['image']);
        $this->assertNull($moods['gentle']['image']);
        $this->assertSame($moods['dramatic']['image'], $response->viewData('choices')['dramatic']['image']);
        $this->assertSame(4, substr_count($response->getContent(), 'class="explore-artwork"'));
        Piece::query()->update(['cover_path' => null]);
        $fallback = $this->get(route('webapp.explore'))->assertOk()->viewData('moods')['playful']['image'];
        $this->assertStringContainsString('/images/backgrounds/periods/baroque', $fallback);
    }

    public function test_inline_examples_include_identity_and_preserve_web_media_access()
    {
        $params = ['level' => 'elementary', 'mood' => 'dramatic'];
        $response = $this->get(route('webapp.explore', $params))->assertOk();
        $example = $response->viewData('guide')['example'];
        $this->assertTrue($example->relationLoaded('composer'));
        $response->assertSee('data-explore-example', false)->assertSee('data-preview="0"', false)
            ->assertSee($example->short_name)->assertSee($example->composer->short_name)
            ->assertSee(route('webapp.pieces.show', $example))->assertSee($example->audio);
        Piece::whereKey($example->id)->update(['is_free' => false]);
        $this->get(route('webapp.explore', $params))->assertOk()->assertSee('data-preview="10"', false);
        $user = Model::withoutEvents(function () { return create(User::class, ['super_user' => true]); });
        $this->actingAs($user, 'web')->get(route('webapp.explore', $params))->assertOk()->assertSee('data-preview="0"', false);
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

    public function test_mood_and_tag_guides_replace_the_level_and_keep_their_filters()
    {
        $response = $this->get(route('webapp.explore', ['mood' => 'dramatic']))->assertOk()->assertSee('By level');
        $this->assertSame('Dramatic', $response->viewData('guide')['title']);
        $this->assertNull($response->viewData('selected'));
        $this->assertSame(13, $response->viewData('guide')['count']);
        $choices = $response->viewData('choices')->keyBy('label');
        $this->assertSame(12, $choices['Elementary']['count']);
        $this->assertSame(1, $choices['Advanced']['count']);
        $params = $choices['Elementary']['params'];
        $this->assertSame(['mood' => 'dramatic', 'level' => 'elementary'], $params);
        $response->assertSee(route('webapp.explore', $params));
        $next = $this->get(route('webapp.explore', $params))->assertOk();
        $this->assertSame('Elementary', $next->viewData('guide')['title']);
        $this->assertSame(12, $next->viewData('guide')['count']);
        $this->assertSame([['label' => 'Dramatic', 'params' => ['mood' => 'dramatic']]], $next->viewData('breadcrumbs'));
        $nextParams = $params + ['tag' => $this->tags['left hand']->id];
        $last = $this->get(route('webapp.explore', $nextParams))->assertOk();
        $this->assertSame('Left hand', $last->viewData('guide')['title']);
        $this->assertSame(2, $last->viewData('guide')['count']);
        $this->assertSame($params, $last->viewData('breadcrumbs')[1]['params']);
        $this->assertSame(2, substr_count($this->getJson(ExploreCatalogue::url($nextParams))->assertOk()->getContent(), 'data-sort-name='));
        foreach (['left hand' => 2, 'baroque' => 13] as $name => $count) {
            $guide = $this->get(route('webapp.explore', ['tag' => $this->tags[$name]->id]))->assertOk()->assertSee('By level');
            $this->assertSame(ucfirst($name), $guide->viewData('guide')['title']);
            $this->assertSame($count, $guide->viewData('guide')['count']);
        }
        $reverse = $this->get(route('webapp.explore', ['level' => 'elementary', 'mood' => 'dramatic']))->assertOk();
        $this->assertSame('Dramatic', $reverse->viewData('guide')['title']);
        $this->assertSame(['level' => 'elementary'], $reverse->viewData('breadcrumbs')[0]['params']);
        if ($path = getenv('EXPLORE_PREVIEW')) {
            file_put_contents(dirname($path).'/mood.html', $response->getContent());
            file_put_contents(dirname($path).'/refined.html', $next->getContent());
        }
    }

    public function test_directory_links_start_fresh_guides_and_keep_menu_thresholds()
    {
        $tag = $this->tags['left hand'];
        foreach ($this->pieces->slice(2, 5) as $piece) $piece->tags()->attach($tag);
        $response = $this->get(route('webapp.explore'))->assertOk();
        $this->assertFalse($response->viewData('tags')->contains('id', $tag->id));
        $this->pieces[7]->tags()->attach($tag);
        $response = $this->get(route('webapp.explore', ['level' => 'advanced']))->assertOk();
        $this->assertTrue($response->viewData('tags')->contains('id', $tag->id));
        if ($path = getenv('EXPLORE_PREVIEW')) {
            file_put_contents(dirname($path).'/technique.html', $this->get(route('webapp.explore', ['tag' => $tag->id]))->getContent());
        }
        $response->assertSee(route('webapp.explore', ['mood' => 'gentle']), false)
            ->assertSee(route('webapp.explore', ['tag' => $tag->id]), false)
            ->assertSee(route('webapp.explore', ['tag' => $this->tags['baroque']->id]), false)
            ->assertSee(route('webapp.highlights'), false);
        $this->withExceptionHandling();
        $this->getJson(route('webapp.explore', ['mood' => 'invalid']))->assertStatus(422);
        $this->getJson(route('webapp.explore', ['tag' => ['bad']]))->assertStatus(422);
        $this->get(route('webapp.explore', ['tag' => $this->tags['agitated']->id]))->assertNotFound();
    }

    public function test_technique_refinements_require_a_match_in_the_current_selection()
    {
        $tag = $this->tags['left hand'];
        foreach ($this->pieces->slice(2, 6) as $piece) $piece->tags()->attach($tag);
        $this->pieces[12]->tags()->attach($this->tags['happy']);
        foreach ([['level' => 'advanced'], ['level' => 'elementary', 'mood' => 'playful']] as $params) {
            $response = $this->get(route('webapp.explore', $params))->assertOk();
            $this->assertTrue($response->viewData('tags')->contains('id', $tag->id));
            $this->assertCount(0, $response->viewData('techniques'));
            $response->assertDontSee(route('webapp.explore', $params + ['tag' => $tag->id]));
        }
        $this->pieces[12]->tags()->attach($tag);
        $params = ['level' => 'advanced', 'mood' => 'playful'];
        $response = $this->get(route('webapp.explore', $params))->assertOk();
        $this->assertSame([$tag->id], $response->viewData('techniques')->pluck('id')->all());
        $response->assertSee(route('webapp.explore', $params + ['tag' => $tag->id]));
        $this->assertSame(1, app(ExploreCatalogue::class)->query($params + ['tag' => $tag->id])->count());
        $this->get(route('webapp.explore', ['tag' => $tag->id]))->assertOk()->assertViewHas('techniques', function ($techniques) {
            return $techniques->isEmpty();
        });
    }

    public function test_composer_guides_preserve_identity_filters_when_refining()
    {
        Composer::query()->update(['gender' => 'male']);
        $composer = $this->pieces[0]->composer;
        Model::withoutEvents(function () use ($composer) { $composer->update(['gender' => 'female']); });
        $response = $this->get(route('webapp.explore', ['composers' => 'women']))->assertOk()->assertSee('By level');
        $this->assertSame('Women composers', $response->viewData('guide')['title']);
        $this->assertSame(1, $response->viewData('guide')['count']);
        $params = ['composers' => 'women', 'level' => 'elementary', 'mood' => 'dramatic'];
        $this->get(route('webapp.explore', $params))->assertOk()->assertViewHas('guide', function ($guide) use ($params) {
            return $guide['params'] === $params && $guide['count'] === 1 && $guide['title'] === 'Dramatic';
        });
        $this->getJson(ExploreCatalogue::url($params))->assertOk()->assertSee('Guided fixture 0')->assertDontSee('Guided fixture 1');
        $this->get(route('webapp.explore', ['country' => $composer->country_id]))->assertOk()->assertSee('By level')
            ->assertViewHas('guide', function ($guide) use ($composer) { return $guide['title'] === $composer->country->name; });
        $this->get(route('webapp.explore', ['composers' => 'all']))->assertOk()->assertViewHas('guide', function ($guide) { return $guide['count'] === 13; });
    }

    public function test_composer_groups_match_catalogue_metadata_in_guides_results_and_directory()
    {
        Composer::query()->update(['date_of_birth' => '1800-01-01', 'date_of_death' => '1880-01-01', 'ethnicity' => 'white', 'is_pedagogical' => false]);
        $groups = [
            'living' => ['date_of_birth' => '1980-01-01', 'date_of_death' => null],
            'black' => ['ethnicity' => 'black'],
            'latin-american' => ['ethnicity' => 'latin american'],
            'asian' => ['ethnicity' => 'asian'],
            'pedagogical' => ['is_pedagogical' => true],
        ];
        // A missing death date alone does not qualify missing or future birth records.
        Composer::whereKey($this->pieces[10]->composer_id)->update(['date_of_birth' => null, 'date_of_death' => null]);
        Composer::whereKey($this->pieces[11]->composer_id)->update(['date_of_birth' => today()->addYear(), 'date_of_death' => null]);
        foreach (array_keys($groups) as $index => $group) {
            $piece = $this->pieces[$index];
            Composer::whereKey($piece->composer_id)->update($groups[$group]);
            // The existing directory's atLeast helper requires two works outside production.
            Model::withoutEvents(function () use ($piece) { create(Piece::class, ['composer_id' => $piece->composer_id]); });
            $label = \App\Services\WebApp\ComposerGroups::OPTIONS[$group]['label'];
            $response = $this->get(route('webapp.explore', ['composers' => $group]))->assertOk()->assertSee('By level');
            $this->assertSame($label, $response->viewData('guide')['title']);
            $this->assertSame(2, $response->viewData('guide')['count']);
            $params = ['composers' => $group, 'level' => 'elementary', 'mood' => 'dramatic'];
            $this->get(route('webapp.explore', $params))->assertOk()->assertViewHas('guide', function ($guide) { return $guide['count'] === 1; });
            $results = $this->getJson(ExploreCatalogue::url($params))->assertOk()->assertSee($piece->name);
            $this->assertSame(1, substr_count($results->getContent(), 'data-sort-name='));
            $this->get(route('webapp.composers.index', ['composers' => $group]))->assertOk()->assertSee($label)
                ->assertViewHas('composers', function ($composers) use ($piece) { return $composers->count() === 1 && $composers->first()->id === $piece->composer_id; });
        }
        $this->withExceptionHandling();
        foreach (['invalid', ['black']] as $group) {
            $this->getJson(route('webapp.explore', ['composers' => $group]))->assertStatus(422);
            $this->getJson(ExploreCatalogue::url(['composers' => $group]))->assertStatus(422);
            $this->getJson(route('webapp.composers.index', ['composers' => $group]))->assertStatus(422);
        }
    }

    public function test_empty_catalogue_remains_browsable()
    {
        \Illuminate\Support\Facades\DB::table('piece_tag')->delete();
        \Illuminate\Support\Facades\DB::table('tags')->delete();
        $this->get(route('webapp.explore'))->assertOk()->assertSee('Levels are being prepared.')
            ->assertSee('Find your next piece')->assertSee('Periods and styles are being prepared.');
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
