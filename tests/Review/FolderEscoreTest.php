<?php

namespace Tests\Review;

use App\{Favorite, FavoriteFolder, Piece, User};
use App\PDF\PDFGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Event, Redis};

class FolderEscoreTest extends ReviewTestCase
{
    private $user, $folder, $pieces;

    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Event::fake([\App\Events\eScoreGenerated::class]);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->user = create(User::class, ['super_user' => false]);
            $this->folder = FavoriteFolder::create(['user_id' => $this->user->id, 'name' => 'Pieces for beginners', 'description' => 'For late elementary to beginner levels']);
            $this->pieces = collect();
            foreach ([['is_free' => true], ['is_free' => false], ['is_free' => true, 'score_url' => 'https://example.com'], ['is_free' => true, 'score_path' => null]] as $i => $attributes) {
                $piece = create(Piece::class, array_merge(['score_path' => 'fixture.pdf'], $attributes));
                Favorite::create(['user_id' => $this->user->id, 'favorite_folder_id' => $this->folder->id, 'piece_id' => $piece->id, 'order' => 10 - $i]);
                $this->pieces->push($piece);
            }
        });
    }

    private function url($parameters = [])
    {
        return route('webapp.users.favorites.folders.pdf', $this->folder).'?'.http_build_query(array_merge(['title' => $this->folder->name], $parameters));
    }

    private function expectExport($ids, $options)
    {
        $generator = \Mockery::mock(PDFGenerator::class);
        $generator->shouldReceive('pieces')->once()->withArgs(function ($pieces) use ($ids) { return $pieces->pluck('id')->values()->all() === $ids; })->andReturnSelf();
        $generator->shouldReceive('request')->once()->with(array_merge($options, ['creator' => $this->user->full_name]), true, true)->andReturnSelf();
        $generator->shouldReceive('generate')->once()->andReturnSelf();
        $generator->shouldReceive('stream')->once()->andReturn(response('PDF fixture'));
        $this->app->instance(PDFGenerator::class, $generator);
    }

    public function test_export_uses_saved_order_description_and_super_user_override()
    {
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $this->expectExport([$this->pieces[1]->id, $this->pieces[0]->id], ['title' => $this->folder->name, 'comment' => $this->folder->description]);
        $this->get($this->url(['user_id' => 999]))->assertOk()->assertSee('PDF fixture');
        Event::assertDispatched(\App\Events\eScoreGenerated::class);
    }

    public function test_unsubscribed_folder_owner_cannot_export_even_the_free_pick()
    {
        $this->actingAs($this->user, 'web')->withExceptionHandling();
        $this->getJson($this->url())->assertForbidden()->assertJsonPath('message', 'Go Premium to create eScores.');
        foreach ([true, false] as $preview) $this->postJson(route('webapp.users.favorites.folders.pdf', $this->folder), ['title' => 'Book', 'piece_ids' => [$this->pieces[0]->id], 'preview' => $preview])->assertForbidden();
        $html = view('webapp.user.my-pieces.favorites.folders.pdf', ['folder' => $this->folder])->render();
        $this->assertStringContainsString('data-bs-target="#piece-upgrade-modal"', $html);
        $this->assertStringNotContainsString('data-escore-form', $html);
        Event::assertNotDispatched(\App\Events\eScoreGenerated::class);
    }

    public function test_cover_form_prefills_folder_metadata_and_counts_only_eligible_scores()
    {
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $html = view('webapp.user.my-pieces.favorites.folders.pdf', ['folder' => $this->folder])->render();
        $this->assertStringContainsString('value="'.$this->folder->name.'"', $html);
        $this->assertStringContainsString($this->folder->description.'</textarea>', $html);
        $this->assertStringContainsString('data-folder-score-count>2</span>', $html);
        $this->assertStringContainsString('name="color" value="#00a2ff"', $html);
    }

    public function test_deliberately_blank_description_and_subtitle_stay_blank()
    {
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $options = ['title' => $this->folder->name, 'subtitle' => '', 'comment' => ''];
        $this->expectExport([$this->pieces[1]->id, $this->pieces[0]->id], $options);
        $this->get($this->url($options))->assertOk();
    }

    public function test_export_requires_session_ownership_and_at_least_one_eligible_score()
    {
        $this->withExceptionHandling()->get($this->url())->assertRedirect(route('login'));
        $other = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($other, 'web')->getJson($this->url())->assertForbidden();
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $this->pieces->each(function ($piece) { $piece->update(['score_path' => null]); });
        $this->getJson($this->url())->assertForbidden();
        Event::assertNotDispatched(\App\Events\eScoreGenerated::class);
    }

    public function test_cover_fields_reject_arrays_long_text_and_css_injection()
    {
        $this->actingAs($this->user, 'web')->withExceptionHandling();
        $this->user->update(['super_user' => true]);
        foreach ([['title' => ['wrong']], ['title' => str_repeat('a', 161)], ['comment' => str_repeat('a', 601)], ['color' => 'red; background: url(x)'], ['subtitle' => ['wrong']]] as $parameters) {
            $this->getJson($this->url($parameters))->assertStatus(422);
        }
    }
    public function test_post_preview_uses_selection_order_private_response_and_no_generation_event()
    {
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $ids = [$this->pieces[0]->id, $this->pieces[1]->id];
        $savedOrder = $this->folder->favorites->pluck('id')->all();
        $generator = \Mockery::mock(PDFGenerator::class);
        $generator->shouldReceive('pieces')->once()->withArgs(function ($pieces) use ($ids) { return $pieces->pluck('id')->all() === $ids; })->andReturnSelf();
        $generator->shouldReceive('request')->once()->withArgs(function ($options) use ($ids) { return $options['piece_ids'] === $ids && $options['page_size'] === 'letter' && $options['creator'] === $this->user->full_name; })->andReturnSelf();
        $generator->shouldReceive('generate')->once()->andReturnSelf();
        $generator->shouldReceive('output')->once()->andReturn('%PDF-preview');
        $generator->shouldReceive('metadata')->once()->andReturn(['pages' => 8, 'entries' => [], 'sections' => ['cover' => 1, 'index' => 1]]);
        $this->app->instance(PDFGenerator::class, $generator);
        $response = $this->postJson(route('webapp.users.favorites.folders.pdf', $this->folder), ['title' => 'Preview', 'piece_ids' => $ids, 'page_size' => 'letter', 'preview' => true, 'creator' => 'Spoofed author']);
        $response->assertOk()->assertJsonPath('pages', 8)->assertJsonPath('pdf', base64_encode('%PDF-preview'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame($savedOrder, $this->folder->fresh()->favorites->pluck('id')->all());
        Event::assertNotDispatched(\App\Events\eScoreGenerated::class);
    }

    public function test_selection_rejects_foreign_restricted_duplicate_and_empty_piece_ids()
    {
        $this->actingAs($this->user, 'web')->withExceptionHandling();
        $this->user->update(['super_user' => true]);
        foreach ([[999999], [$this->pieces[2]->id], [$this->pieces[3]->id], [$this->pieces[0]->id, $this->pieces[0]->id], []] as $ids) {
            $this->postJson(route('webapp.users.favorites.folders.pdf', $this->folder), ['title' => 'Book', 'piece_ids' => $ids, 'preview' => true])->assertStatus(422);
        }
        $this->postJson(route('webapp.users.favorites.folders.pdf', $this->folder), ['title' => 'Book', 'page_size' => 'landscape'])->assertStatus(422)->assertJsonValidationErrors('page_size');
        Event::assertNotDispatched(\App\Events\eScoreGenerated::class);
    }

    public function test_three_step_editor_has_all_options_and_no_orientation_selector()
    {
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $html = view('webapp.user.my-pieces.favorites.folders.pdf', ['folder' => $this->folder])->render();
        foreach (['escore-modal', 'data-escore-panel="1"', 'data-escore-panel="2"', 'data-escore-panel="3"', 'name="page_numbers"', 'name="composer_names"', 'name="include_edition"', 'name="blank_pages"', 'name="page_size"', 'name="_token"'] as $token) $this->assertStringContainsString($token, $html);
        $this->assertStringContainsString('data-escore-drag', $html);
        $this->assertStringNotContainsString('data-escore-order', $html);
        $this->assertStringNotContainsString('Landscape', $html);
        $this->assertStringNotContainsString('name="orientation"', $html);
    }

    public function test_post_download_returns_a_pdf_and_dispatches_the_existing_generation_event()
    {
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $generator = \Mockery::mock(PDFGenerator::class);
        $generator->shouldReceive('pieces')->once()->withArgs(function ($pieces) { return $pieces->pluck('id')->all() === [$this->pieces[0]->id]; })->andReturnSelf();
        $generator->shouldReceive('request')->once()->andReturnSelf();
        $generator->shouldReceive('generate')->once()->andReturnSelf();
        $generator->shouldReceive('download')->once()->andReturn(response('%PDF-fixture', 200, ['Content-Type' => 'application/pdf']));
        $this->app->instance(PDFGenerator::class, $generator);
        $this->postJson(route('webapp.users.favorites.folders.pdf', $this->folder), ['title' => 'Book', 'piece_ids' => [$this->pieces[0]->id], 'preview' => false])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        Event::assertDispatchedTimes(\App\Events\eScoreGenerated::class, 1);
    }

}
