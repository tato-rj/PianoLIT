<?php

namespace Tests\Feature;

use App\{Piece, Admin};
use Tests\AppTest;
use Tests\Traits\ManageDatabase;
use Illuminate\Http\UploadedFile;

class PieceTest extends AppTest
{
    use ManageDatabase;

    /** @test */
    public function an_admin_can_add_pieces()
    {
        \Storage::fake('public');

        $this->signIn();

        $piece = $this->postPiece();

        $this->assertDatabaseHas('pieces', ['name' => $piece['name']]);
    }

    /** @test */
    public function admins_can_edit_a_piece()
    {
        \Storage::fake('public');

        $this->signIn();

        $updatedPiece = $this->updatePiece($this->piece);

        $this->assertEquals($this->piece->fresh()->name, $updatedPiece['name']);
    }

    /** @test */
    public function a_new_tutorial_can_be_added()
    {
        $this->signIn();

        $this->assertEquals(1, $this->piece->tutorials()->count());

        $newTutorial = [
            'type' => 'NEW TITLE',
            'description' => 'NEW DESCRIPTION',
            'filename' => 'NEW FILENAME'
        ];

        $this->updatePiece($this->piece, ['videos' => [$this->piece->tutorials->first()->toArray(), $newTutorial]]);

        $this->assertEquals(2, $this->piece->tutorials()->count());
    }

    /** @test */
    public function a_tutorial_can_be_edited()
    {
        $this->signIn();

        $originalTutorial = $this->piece->tutorials->first();

        $editedTutorials = [
            'id' => $originalTutorial->id,
            'type' => 'NEW TITLE',
            'description' => $originalTutorial->description,
            'filename' => $originalTutorial->filename
        ];

        $updatedPiece = $this->updatePiece($this->piece, ['videos' => [$editedTutorials]]);

        $this->assertNotEquals($originalTutorial->type, $this->piece->tutorials->fresh()->first()->type);
    }

    /** @test */
    public function a_tutorial_can_be_removed()
    {
        $this->signIn();

        $newTutorial = [
            'type' => 'NEW TITLE',
            'description' => 'NEW DESCRIPTION',
            'filename' => 'NEW FILENAME'
        ];

        $this->updatePiece($this->piece, ['videos' => [$this->piece->tutorials->first()->toArray(), $newTutorial]]);

        $this->assertEquals(2, $this->piece->tutorials()->count());

        $this->updatePiece($this->piece, ['videos' => [$newTutorial]]);

        $this->assertEquals(1, $this->piece->tutorials()->count());
    }

    /** @test */
    public function all_tutorials_can_be_removed()
    {
        $this->signIn();

        $this->assertEquals(1, $this->piece->tutorials()->count());

        $this->updatePiece($this->piece);

        $this->assertEquals(0, $this->piece->tutorials()->count());
    }

    /** @test */
    public function an_admin_can_select_the_highlighted_piece_of_the_week()
    {
        $this->signIn();

        $this->assertFalse($this->piece->is_free);

        $this->patch(route('admin.pieces.highlight', $this->piece->id));

        $this->assertTrue($this->piece->fresh()->is_free);
    }

    /** @test */
    public function only_one_piece_is_highlighted_at_a_time()
    {
        $this->signIn();

        $old = create(Piece::class, ['is_free' => true]);

        $this->assertTrue($old->is_free);
        $this->assertFalse($this->piece->is_free);

        $this->patch(route('admin.pieces.highlight', $this->piece->id));

        $this->assertFalse($old->fresh()->is_free);
        $this->assertTrue($this->piece->fresh()->is_free);
    }

    /** @test */
    public function the_original_file_is_removed_when_a_new_one_is_uploaded()
    {
        \Storage::fake('public');

        $this->signIn();

        $piece = $this->postPiece();

        $updatedPiece = $this->updatePiece($piece);

        $original_audio = $piece->audio_path;
        $original_score = $piece->score_path;

        $this->patch(route('admin.pieces.update', $piece->id), $updatedPiece);

        \Storage::disk('public')->assertMissing($original_audio);
        \Storage::disk('public')->assertExists($piece->fresh()->audio_path);
        \Storage::disk('public')->assertMissing($original_score);
        \Storage::disk('public')->assertExists($piece->fresh()->score_path);
    }

    /** @test */
    public function admins_can_delete_pieces()
    {
        \Storage::fake('public');

        $this->signIn();

        $piece_id = $this->piece->id;

        $this->delete(route('admin.pieces.destroy', $this->piece->id));

        $this->assertDatabaseMissing('pieces', ['id' => $piece_id]);
    }

    /** @test */
    public function the_tags_relationships_are_removed_when_a_piece_is_deleted()
    {
        \Storage::fake('public');
        
        $this->signIn();

        $piece_id = $this->piece->id;

        $this->delete(route('admin.pieces.destroy', $this->piece->id));

        $this->assertDatabaseMissing('piece_tag', ['piece_id' => $piece_id]);
    }

    /** @test */
    public function all_favorited_relationships_are_removed_when_a_piece_is_removed()
    {
        $this->signIn();

        $piece_id = $this->piece->id;

        $this->delete(route('admin.pieces.destroy', $this->piece->id));

        $this->assertDatabaseMissing('favorites', ['piece_id' => $piece_id]);
    }

    /** @test */
    public function all_related_files_are_removed_when_a_piece_is_deleted()
    {
        \Storage::fake('public');

        $this->signIn();

        $piece = $this->postPiece();

        $this->delete(route('admin.pieces.destroy', $piece->id));

        \Storage::disk('public')->assertMissing($piece->autio_path);
        \Storage::disk('public')->assertMissing($piece->audio_rh_path);
        \Storage::disk('public')->assertMissing($piece->audio_lh_path);
        \Storage::disk('public')->assertMissing($piece->score_path);
    }

    /** @test */
    public function unauthorized_admins_cannot_delete_pieces()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $admin = create(Admin::class, ['role' => 'unauthorized']);

        $this->signIn($admin);

        $this->delete(route('admin.pieces.destroy', $this->piece->id));
    }
}
