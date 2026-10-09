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
        $this->assertSame(6, $data['tag-'.$this->tags['agitated']->id]['count']);
        $this->assertSame(6, $data['tag-'.$this->tags['crazy']->id]['count']);
        $this->assertSame(ExploreCatalogue::LEVELS, $response->viewData('levels')->pluck('name')->all());
        $this->get(route('webapp.explore', ['level' => 'advanced']))->assertOk()->assertSee('View all 1 piece');
        if (getenv('EXPLORE_PREVIEW')) {
            file_put_contents(getenv('EXPLORE_PREVIEW'), $response->getContent());
            file_put_contents(dirname(getenv('EXPLORE_PREVIEW')).'/level.html', $this->get(route('webapp.explore', ['level' => 'elementary']))->getContent());
        }
    }

    public function test_explore_disables_ipad_auto_shrinking_without_changing_other_pages()
    {
        foreach ([[], ['level' => 'elementary']] as $params) {
            $html = $this->get(route('webapp.explore', $params))->assertOk()->getContent();
            preg_match('/<meta name="viewport" content="([^"]+)"/', $html, $viewport);
            $this->assertStringContainsString('width=device-width', $viewport[1]);
            $this->assertStringContainsString('shrink-to-fit=no', $viewport[1]);
        }
        $this->get(route('webapp.highlights'))->assertOk()->assertDontSee('shrink-to-fit=no');
    }

    public function test_different_moods_never_repeat_an_image_even_for_shared_piece_covers()
    {
        foreach ($this->pieces as $piece) $piece->tags()->detach($this->tags->only(['agitated', 'crazy'])->pluck('id'));
        [$dreamy, $reflective] = Model::withoutEvents(function () {
            return [create(Tag::class, ['name' => 'dreamy', 'type' => 'mood']), create(Tag::class, ['name' => 'reflective', 'type' => 'mood'])];
        });
        Piece::whereIn('id', $this->pieces->take(2)->pluck('id'))->update(['cover_path' => 'shared.jpg']);
        Piece::whereKey($this->pieces[2]->id)->update(['cover_path' => 'unique.jpg']);
        $this->pieces[0]->tags()->attach($dreamy);
        $this->pieces[1]->tags()->attach($reflective);
        $this->pieces[2]->tags()->attach($reflective);
        $response = $this->get(route('webapp.explore', ['level' => 'elementary']))->assertOk();
        $moods = $response->viewData('moods');
        $this->assertSame(storage('shared.jpg'), $moods['tag-'.$dreamy->id]['image']);
        $this->assertSame(storage('unique.jpg'), $moods['tag-'.$reflective->id]['image']);
        $this->assertSame($moods['tag-'.$reflective->id]['image'], $response->viewData('choices')['tag-'.$reflective->id]['image']);
        $this->pieces[2]->tags()->detach($reflective);
        $moods = $this->get(route('webapp.explore'))->assertOk()->viewData('moods');
        $this->assertNull($moods['tag-'.$reflective->id]['image'], 'Use the existing icon when all related covers are already used.');
        Piece::query()->update(['cover_path' => null]);
        $moods = $this->get(route('webapp.explore'))->assertOk()->viewData('moods');
        $this->assertNotNull($moods['tag-'.$dreamy->id]['image']);
        $this->assertNotNull($moods['tag-'.$reflective->id]['image']);
        $this->assertNotSame($moods['tag-'.$dreamy->id]['image'], $moods['tag-'.$reflective->id]['image']);
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
        $this->assertContains($moods['tag-'.$this->tags['agitated']->id]['image'], $covers);
        $this->assertSame($covers[0], $moods['tag-'.$this->tags['happy']->id]['image']);
        $this->assertFalse($moods->has('gentle'), 'Empty moods are omitted from the ranked directory.');
        $this->assertSame($moods['tag-'.$this->tags['agitated']->id]['image'], $response->viewData('choices')['tag-'.$this->tags['agitated']->id]['image']);
        $this->assertSame(6, substr_count($response->getContent(), 'class="explore-artwork"'));
        Piece::query()->update(['cover_path' => null]);
        $fallback = $this->get(route('webapp.explore'))->assertOk()->viewData('moods')['tag-'.$this->tags['happy']->id]['image'];
        $this->assertStringContainsString('/images/backgrounds/periods/baroque', $fallback);
    }

    public function test_explore_omits_audio_examples_and_duplicate_terminal_actions()
    {
        foreach ([[], ['level' => 'elementary'], ['mood' => 'dramatic'], ['tag' => $this->tags['baroque']->id]] as $params) {
            $this->get(route('webapp.explore', $params))->assertOk()
                ->assertDontSee('Hear an example')->assertDontSee('data-explore-example', false)
                ->assertDontSee('js/views/explore.js', false)->assertSee('View all matching pieces');
        }
        $params = ['level' => 'elementary', 'mood' => 'dramatic'];
        $response = $this->get(route('webapp.explore', $params))->assertOk()
            ->assertDontSee('View all matching pieces')->assertDontSee('Hear an example')
            ->assertSee('View all 12 pieces');
        $this->assertEmpty($response->viewData('choices'));
        $response->assertSee(ExploreCatalogue::url($params, $response->viewData('selectionLabel')));
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

    public function test_contextual_mood_and_level_choices_exclude_zero_matches()
    {
        $this->pieces[12]->tags()->detach($this->tags->only(['agitated', 'crazy'])->pluck('id'));
        $this->pieces[12]->tags()->attach($this->tags['happy']);
        $params = ['tag' => $this->tags['baroque']->id, 'level' => 'advanced'];
        $response = $this->get(route('webapp.explore', $params))->assertOk();
        $this->assertSame(['tag-'.$this->tags['happy']->id], $response->viewData('choices')->keys()->all());
        $this->assertSame(1, $response->viewData('choices')['tag-'.$this->tags['happy']->id]['count']);
        $response->assertDontSee(route('webapp.explore', $params + ['mood' => 'dramatic']));
        $response->assertSee(route('webapp.explore', $params + ['mood' => 'tag-'.$this->tags['happy']->id]));
        $response = $this->get(route('webapp.explore', ['mood' => 'dramatic']))->assertOk();
        $this->assertSame(['Elementary'], $response->viewData('choices')->pluck('label')->all());
        $this->pieces[12]->tags()->detach($this->tags['happy']);
        $response = $this->get(route('webapp.explore', $params))->assertOk();
        $this->assertCount(0, $response->viewData('choices'));
        $response->assertDontSee('By character')->assertDontSee('View all matching pieces')->assertSee('View all 1 piece');
    }

    public function test_every_guide_links_to_highlights_with_its_complete_selection()
    {
        $genre = Model::withoutEvents(function () { return create(Tag::class, ['name' => 'jazz', 'type' => 'genre']); });
        $this->pieces[2]->tags()->attach($genre);
        $this->pieces[1]->tags()->attach($this->tags['happy']);
        $this->pieces[12]->tags()->attach($this->tags['happy']);
        Piece::whereKey($this->pieces[12]->id)->update(['highlighted_at' => null]);
        Composer::query()->update(['gender' => 'male']);
        Composer::whereKey($this->pieces[0]->composer_id)->update(['gender' => 'female']);
        $cases = [
            [['level' => 'elementary'], 'at this level', range(0, 11)],
            [['mood' => 'playful'], 'with this mood', [1]],
            [['tag' => $this->tags['left hand']->id], 'with this technique', [0, 1]],
            [['composers' => 'women'], 'by these composers', [0]],
            [['tag' => $this->tags['baroque']->id], 'from this period/style', range(0, 11)],
            [['tag' => $genre->id], 'from this period/style', [2]],
            [['level' => 'elementary', 'mood' => 'playful', 'tag' => $this->tags['left hand']->id], 'with this technique', [1]],
            [['composers' => 'women', 'country' => $this->pieces[0]->composer->country_id], 'by these composers', [0]],
        ];
        foreach ($cases as [$params, $label, $indexes]) {
            $url = route('webapp.highlights', ['explore' => $params]);
            $this->get(route('webapp.explore', $params))->assertOk()->assertSee('Past highlights '.$label)
                ->assertSee($url)->assertDontSee('Past free picks');
            $response = $this->get($url)->assertOk()->assertSee('All highlights')->assertSee($url);
            $this->assertSame(array_map('strval', $params), $response->viewData('explore'));
            $this->assertEqualsCanonicalizing($this->pieces->only($indexes)->pluck('id')->all(), $response->viewData('pieces')->pluck('id')->all());
            $fragment = $this->getJson($url)->assertOk();
            $this->assertSame(count($indexes), substr_count($fragment->getContent(), 'data-sort-views='));
        }
        $response = $this->getJson(route('webapp.highlights', ['explore' => ['mood' => 'playful'], 'filters' => ['["advanced"]']]))->assertOk();
        $this->assertSame('', $response->getContent());
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
        $response->assertSee(route('webapp.explore', ['mood' => 'tag-'.$this->tags['agitated']->id]), false)
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
        $this->assertSame(1, $response->viewData('techniques')->first()->matching_pieces_count);
        $url = ExploreCatalogue::url($params + ['tag' => $tag->id], $response->viewData('selectionLabel').' · Left hand');
        $response->assertDontSee($url)->assertDontSee('Other ways into this level');
        $this->getJson($url)->assertOk()->assertSee('Guided fixture 12')->assertDontSee('Guided fixture 0');
        $elementary = $this->get(route('webapp.explore', ['level' => 'elementary', 'mood' => 'dramatic']))->assertOk();
        $this->assertSame(8, $elementary->viewData('techniques')->first()->matching_pieces_count);
        $elementary->assertDontSee('<strong>Technique</strong>', false);
        $second = Model::withoutEvents(function () { return create(Tag::class, ['type' => 'technique', 'name' => 'legato']); });
        foreach ($this->pieces->take(8)->push($this->pieces[12]) as $piece) $piece->tags()->attach($second);
        $multiple = $this->get(route('webapp.explore', $params))->assertOk()->assertSee($url);
        $this->assertCount(2, $multiple->viewData('techniques'));
        $this->assertStringNotContainsString(' open', view('webapp.explore.level', $multiple->original->getData())->render());
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

    public function test_length_and_period_refinements_show_contextual_counts_and_open_results()
    {
        Model::withoutEvents(function () {
            foreach (['long', 'medium'] as $name) $this->tags[$name] = create(Tag::class, ['type' => 'length', 'name' => $name]);
            foreach ($this->pieces->take(12) as $index => $piece) {
                $piece->tags()->detach($this->tags['short']);
                $piece->tags()->attach($this->tags[ExploreCatalogue::LENGTHS[$index % 3]]);
            }
        });
        $params = ['level' => 'elementary', 'mood' => 'dramatic'];
        $response = $this->get(route('webapp.explore', $params))->assertOk()->assertSee('<strong>Length</strong>', false)->assertDontSee('<strong>Periods</strong>', false);
        $this->assertStringNotContainsString(' open', view('webapp.explore.level', $response->original->getData())->render());
        $this->assertSame(ExploreCatalogue::LENGTHS, $response->viewData('lengths')->pluck('name')->all());
        $this->assertSame([4, 4, 4], $response->viewData('lengths')->pluck('matching_pieces_count')->all());
        $this->assertSame([$this->tags['baroque']->id], $response->viewData('periods')->pluck('id')->all());
        $this->assertSame(12, $response->viewData('periods')->first()->matching_pieces_count);
        foreach (ExploreCatalogue::LENGTHS as $length) {
            $url = ExploreCatalogue::url($params + ['length' => $length], $response->viewData('selectionLabel').' · '.ucfirst($length));
            $response->assertSee($url);
            $this->assertSame(4, app(ExploreCatalogue::class)->query($params + ['length' => $length])->count());
            $result = $this->getJson($url)->assertOk()->assertDontSee('Guided fixture 12');
            $this->assertSame(3, substr_count($result->getContent(), 'data-sort-name='));
            $expected = $this->pieces->take(12)->filter(function ($piece, $index) use ($length) {
                return ExploreCatalogue::LENGTHS[$index % 3] === $length;
            })->reverse()->take(3)->pluck('id')->values()->all();
            $this->assertSame($expected, app(ExploreCatalogue::class)->results(\Illuminate\Http\Request::create($url))->pluck('id')->all());
        }
        $periodUrl = ExploreCatalogue::url($params + ['tag' => $this->tags['baroque']->id], $response->viewData('selectionLabel').' · Baroque');
        $response->assertDontSee($periodUrl);
        $second = Model::withoutEvents(function () { return create(Tag::class, ['type' => 'period', 'name' => 'romantic']); });
        foreach ($this->pieces->take(12) as $piece) $piece->tags()->attach($second);
        $multiple = $this->get(route('webapp.explore', $params))->assertOk()->assertSee($periodUrl)->assertSee('<strong>Periods</strong>', false);
        $this->assertStringNotContainsString(' open', view('webapp.explore.level', $multiple->original->getData())->render());
        $this->getJson($periodUrl)->assertOk()->assertDontSee('Guided fixture 12');
        $advanced = $this->get(route('webapp.explore', ['level' => 'advanced']))->assertOk();
        $advanced->assertDontSee('Other ways into this level')->assertDontSee('<strong>Length</strong>', false);
        $this->assertSame(['short'], $advanced->viewData('lengths')->pluck('name')->all());
        $this->assertSame(1, $advanced->viewData('lengths')->first()->matching_pieces_count);
        $this->assertSame(1, $advanced->viewData('periods')->first()->matching_pieces_count);
        $empty = $this->get(route('webapp.explore', ['level' => 'elementary', 'mood' => 'gentle']))->assertOk();
        $this->assertEmpty($empty->viewData('lengths'));
        $this->assertEmpty($empty->viewData('periods'));
        $this->withExceptionHandling();
        foreach (['invalid', ['short']] as $length) {
            $this->getJson(ExploreCatalogue::url($params + ['length' => $length]))->assertStatus(422);
        }
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

    public function test_mood_directory_ranks_top_eight_while_contextual_choices_include_every_match()
    {
        $extra = Model::withoutEvents(function () {
            return collect(['bright', 'bold', 'bouncy', 'energetic', 'festive', 'heroic', 'nostalgic', 'peaceful', 'tender'])
                ->map(function ($name, $index) {
                    $tag = create(Tag::class, ['type' => 'mood', 'name' => $name]);
                    foreach ($this->pieces->take($index + 1) as $piece) $piece->tags()->attach($tag);
                    return $tag;
                });
        });
        // Each mood is counted independently even when a piece has several moods.
        $this->pieces[0]->tags()->attach($this->tags['crazy']);
        $this->pieces[0]->tags()->attach($this->tags['happy']);
        $advancedOnly = Model::withoutEvents(function () { return create(Tag::class, ['type' => 'mood', 'name' => 'advanced only']); });
        $empty = Model::withoutEvents(function () { return create(Tag::class, ['type' => 'mood', 'name' => 'empty mood']); });
        $this->pieces[12]->tags()->attach($advancedOnly);

        $response = $this->get(route('webapp.explore', ['level' => 'elementary']))->assertOk();
        $moods = $response->viewData('moods');
        $choices = $response->viewData('choices');
        $this->assertCount(8, $moods);
        $this->assertSame([9, 8, 7, 7, 7, 6, 5, 4], $moods->pluck('total_count')->all());
        $this->assertSame('Tender', $moods->first()['label']);
        $response->assertDontSee('Gentle &amp; lyrical', false)->assertDontSee('Playful &amp; lively', false);
        $response->assertSee('data-explore-mood-choices', false)->assertSee('data-explore-moods-more', false);
        $this->assertCount(12, $choices, 'No eight-mood cap applies to contextual matches.');
        $this->assertSame(6, $choices['tag-'.$this->tags['agitated']->id]['count']);
        $this->assertSame(7, $choices['tag-'.$this->tags['crazy']->id]['count']);
        $this->assertSame(1, $choices['tag-'.$this->tags['happy']->id]['count']);
        $this->assertFalse($choices->has('tag-'.$advancedOnly->id));
        $this->assertFalse($choices->has('tag-'.$empty->id));
        $this->assertSame('Crazy', $choices['tag-'.$this->tags['crazy']->id]['label'], 'Use individual names rather than combined categories.');
        foreach ($extra as $index => $tag) {
            $key = 'tag-'.$tag->id;
            $this->assertSame($index + 1, $choices[$key]['count']);
            $this->assertSame(['level' => 'elementary', 'mood' => $key], $choices[$key]['params']);
            if ($moods->has($key)) $this->assertSame($moods[$key]['image'], $choices[$key]['image']);
        }
        $images = $choices->pluck('image')->filter();
        $this->assertSame($images->count(), $images->unique()->count());
        $advanced = $this->get(route('webapp.explore', ['level' => 'advanced']))->assertOk();
        $this->assertSame($moods->keys()->all(), $advanced->viewData('moods')->keys()->all(), 'Directory ranking remains global.');
        $this->assertEqualsCanonicalizing(['tag-'.$this->tags['agitated']->id, 'tag-'.$advancedOnly->id], $advanced->viewData('choices')->keys()->all());
        $this->assertSame([1, 1], $advanced->viewData('choices')->pluck('count')->all());
        $advanced->assertDontSee('data-explore-moods-more', false);
        $this->assertSame(['Advanced only', 'Agitated'], $advanced->viewData('choices')->pluck('label')->all(), 'Tied counts sort by label.');

        if ($path = getenv('EXPLORE_MOODS_PREVIEW')) {
            file_put_contents($path, $response->getContent());
        }
    }

    public function test_additional_moods_work_through_guides_results_and_highlights_with_all_filters()
    {
        $tag = Model::withoutEvents(function () { return create(Tag::class, ['type' => 'mood', 'name' => 'wistful']); });
        foreach ([0, 1, 12] as $index) $this->pieces[$index]->tags()->attach($tag);
        $key = 'tag-'.$tag->id;
        $guide = $this->get(route('webapp.explore', ['mood' => $key]))->assertOk();
        $this->assertSame('Wistful', $guide->viewData('guide')['title']);
        $this->assertSame(3, $guide->viewData('guide')['count']);
        $params = ['mood' => $key, 'level' => 'elementary', 'country' => $this->pieces[0]->composer->country_id];
        $context = $this->get(route('webapp.explore', array_diff_key($params, ['mood' => true])))->assertOk();
        $this->assertSame(1, $context->viewData('choices')[$key]['count'], 'Additional moods respect the selected country as well as level.');
        $response = $this->get(route('webapp.explore', $params))->assertOk();
        $this->assertSame(1, $response->viewData('guide')['count']);
        $this->assertSame(['mood' => $key], $response->viewData('breadcrumbs')[0]['params']);
        $this->getJson(ExploreCatalogue::url($params))->assertOk()->assertSee('Guided fixture 0')->assertDontSee('Guided fixture 1');
        $highlightsUrl = route('webapp.highlights', ['explore' => $params]);
        $response->assertSee($highlightsUrl);
        $highlights = $this->get($highlightsUrl)->assertOk();
        $this->assertStringContainsString('Wistful', $highlights->viewData('exploreLabels'));
        $this->assertSame([$this->pieces[0]->id], $highlights->viewData('pieces')->pluck('id')->all());
        $this->getJson($highlightsUrl)->assertOk()->assertSee('Guided fixture 0')->assertDontSee('Guided fixture 1');
    }

    public function test_dynamic_mood_validation_rejects_other_tag_types_missing_ids_and_invalid_shapes()
    {
        $this->withExceptionHandling();
        foreach (['tag-'.$this->tags['baroque']->id, 'tag-999999', 'tag-0', 'tag-1 OR 1=1', ['dramatic']] as $mood) {
            $this->getJson(route('webapp.explore', ['mood' => $mood]))->assertStatus(422);
            $this->getJson(ExploreCatalogue::url(['mood' => $mood]))->assertStatus(422);
            $this->getJson(route('webapp.highlights', ['explore' => ['mood' => $mood]]))->assertStatus(422);
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
