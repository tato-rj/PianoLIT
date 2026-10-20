<?php

namespace Tests\Review;

use App\{Admin, Composer, Piece, Tag, Tutorial};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AdminPieceEditorTest extends ReviewTestCase
{
    protected $piece, $video, $tags;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $admin = create(Admin::class, ['role' => 'manager']);
        $this->actingAs($admin, 'admin');
        Model::withoutEvents(function () use ($admin) {
            $this->piece = create(Piece::class, ['creator_id' => $admin->id, 'composer_id' => create(Composer::class)->id,
                'name' => 'Sonatina', 'key' => 'C major', 'cover_path' => 'covers/example.jpg', 'score_path' => 'scores/example.pdf', 'audio_path' => 'audio/example.mp3']);
            $this->tags = collect();
            foreach (['period' => 'classical', 'length' => 'short', 'level' => 'beginner', 'mood' => 'happy'] as $type => $name) {
                $this->tags[$type] = create(Tag::class, compact('type', 'name'));
            }
            $this->piece->tags()->attach($this->tags->pluck('id'));
            $this->video = create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'Performance', 'category' => 'performance', 'filename' => 'old-video.mp4']);
        });
    }

    protected function xpath($html)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }

    public function test_labeled_options_are_separate_from_the_editor_and_leave_the_directory()
    {
        $html = $this->get(route('admin.pieces.edit', $this->piece))->assertOk()->getContent();
        $xpath = $this->xpath($html);
        foreach (['hijack' => 'Set creation date to today'] as $action => $label) {
            $forms = $xpath->query('//form[@action="'.route('admin.pieces.'.$action, $this->piece).'"]');
            $this->assertCount(1, $forms);
            $this->assertCount(0, $xpath->query('ancestor::form', $forms[0]));
            $this->assertCount(1, $xpath->query('.//input[@name="_token"]', $forms[0]));
            $this->assertCount(1, $xpath->query('.//input[@name="_method" and @value="PATCH"]', $forms[0]));
            $this->assertCount(1, $xpath->query('.//button[@type="submit" and contains(., "'.$label.'")]', $forms[0]));
        }
        $view = $xpath->query('//a[@href="'.route('webapp.pieces.show', $this->piece).'"]')[0];
        $this->assertSame('_blank', $view->getAttribute('target'));
        $this->assertSame('noopener noreferrer', $view->getAttribute('rel'));
        $this->assertCount(1, $xpath->query('//form[@id="edit-form" and @enctype="multipart/form-data"]'));
        $this->assertCount(1, $xpath->query('//button[@form="delete-score-form"]'));
        $this->assertCount(1, $xpath->query('//form[@id="delete-score-form"]//input[@value="deleteScore"]'));
        $this->assertCount(0, $xpath->query('//input[@name="is_public" and contains(@class,"status-toggle")]'));
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $element) $ids[] = $element->getAttribute('id');
        $this->assertCount(count(array_unique($ids)), $ids, 'Labels and cloned video controls have unique IDs.');
        $this->assertStringContainsString(route('admin.pieces.videos.moments.edit', [$this->piece, $this->video]), $html);
        $this->get(route('admin.pieces.index'))->assertOk()->assertDontSee(route('admin.pieces.highlight', $this->piece), false)->assertDontSee(route('admin.pieces.hijack', $this->piece), false);
        $this->assertStringContainsString('Schedule', view('admin.pages.pieces.table.actions', ['item' => $this->piece])->render());
    }

    public function test_validation_return_restores_fields_choices_and_existing_video_identity()
    {
        $this->withSession(['_old_input' => [
            'name' => 'Updated <name>', 'description' => '<script>plain text</script>',
            'composer_id' => $this->piece->composer_id, 'key' => 'G minor',
            'period' => [$this->tags['period']->id], 'length' => [$this->tags['length']->id], 'level' => [$this->tags['level']->id],
            'videos' => [3 => ['id' => $this->video->id, 'type' => 'Performance', 'category' => 'performance', 'description' => 'Edited clip', 'filename' => 'new-video.mp4']],
        ]]);
        $html = $this->get(route('admin.pieces.edit', $this->piece))->assertOk()->getContent();
        $xpath = $this->xpath($html);
        $this->assertSame('Updated <name>', $xpath->query('//input[@name="name"]')[0]->getAttribute('value'));
        $this->assertSame('<script>plain text</script>', $xpath->query('//textarea[@name="description"]')[0]->textContent);
        $this->assertCount(0, $xpath->query('//textarea/script'));
        $this->assertCount(1, $xpath->query('//select[@name="level[]"]/option[@selected and @value="'.$this->tags['level']->id.'"]'));
        $this->assertCount(0, $xpath->query('//input[@name="tags[]" and @checked]'));
        $this->assertCount(0, $xpath->query('//input[@name="is_public" and @checked]'));
        $this->assertCount(1, $xpath->query('//input[@name="videos[0][id]" and @value="'.$this->video->id.'"]'));
        $this->assertSame('new-video.mp4', $xpath->query('//input[@name="videos[0][filename]"]')[0]->getAttribute('value'));
    }

    public function test_saving_form_metadata_preserves_media_paths_and_tutorial_ids()
    {
        $payload = ['name' => 'Edited sonatina', 'key' => 'G minor', 'composer_id' => $this->piece->composer_id,
            'period' => [$this->tags['period']->id], 'length' => [$this->tags['length']->id], 'level' => [$this->tags['level']->id],
            'tags' => [$this->tags['mood']->id], 'is_public' => 'on', 'is_attributed_to' => 'on', 'show_on_tour' => 'on',
            'score_editor' => 'Editor', 'score_publisher' => 'Publisher', 'description' => 'Updated description',
            'videos' => [['id' => $this->video->id, 'type' => 'Performance', 'category' => 'performance', 'description' => 'Edited clip', 'filename' => 'new-video.mp4']]];
        Model::withoutEvents(function () use ($payload) {
            $this->patch(route('admin.pieces.update', $this->piece), $payload)->assertRedirect()->assertSessionHas('status');
        });
        $piece = $this->piece->fresh();
        $this->assertSame('Edited sonatina', $piece->name);
        $this->assertSame('G minor', $piece->key);
        $this->assertSame('covers/example.jpg', $piece->cover_path);
        $this->assertSame('scores/example.pdf', $piece->score_path);
        $this->assertSame('audio/example.mp3', $piece->audio_path);
        $this->assertEqualsCanonicalizing($this->tags->pluck('id')->all(), $piece->tags->pluck('id')->all());
        $this->assertSame([$this->video->id], $piece->tutorials->pluck('id')->all());
        $this->assertSame('new-video.mp4', $this->video->fresh()->filename);
    }

    public function test_current_freepick_and_read_only_editor_states_are_preserved()
    {
        setFreePick($this->piece, true);
        $xpath = $this->xpath($this->get(route('admin.pieces.edit', $this->piece))->assertOk()->getContent());
        $this->assertCount(0, $xpath->query('//button[contains(.,"Freepick")]'));
        $this->assertCount(0, $xpath->query('//form[@action="'.route('admin.pieces.highlight', $this->piece).'"]'));
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $xpath = $this->xpath($this->get(route('admin.pieces.edit', $this->piece))->assertOk()->getContent());
        $this->assertCount(1, $xpath->query('//form[@id="edit-form"]/fieldset[@disabled]'));
        $this->assertCount(0, $xpath->query('//*[@data-save-piece]'));
        $this->assertCount(0, $xpath->query('//input[@name="score"]'));
        $this->withExceptionHandling()->patchJson(route('admin.pieces.update', $this->piece), [])->assertForbidden();
        auth()->guard('admin')->logout();
        $this->get(route('admin.pieces.edit', $this->piece))->assertRedirect(route('admin.login.show'));
    }
}
