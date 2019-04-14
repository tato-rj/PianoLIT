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
    public function audio_files_are_uploaded_when_a_piece_is_created()
    {
        \Storage::fake('public');

        $this->signIn();

        $piece = $this->postPiece()->fresh();

        \Storage::disk('public')->assertExists($piece->audio_path);
    }

    /** @test */
    public function the_score_is_uploaded_when_a_piece_is_created()
    {
        \Storage::fake('public');

        $this->signIn();

        $piece = $this->postPiece()->fresh();

        \Storage::disk('public')->assertExists($piece->score_path);
    }

    /** @test */
    public function admins_can_edit_a_piece()
    {
        $this->signIn();

        $updatedPiece = $this->updatePiece($this->piece);

        $this->assertEquals($this->piece->fresh()->name, $updatedPiece['name']);
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
        $this->signIn();

        $piece_id = $this->piece->id;

        $this->delete(route('admin.pieces.destroy', $this->piece->id));

        $this->assertDatabaseMissing('pieces', ['id' => $piece_id]);
    }

    /** @test */
    public function the_tags_relationships_are_removed_when_a_piece_is_deleted()
    {
        $this->signIn();

        $piece_id = $this->piece->id;

        $this->delete(route('admin.pieces.destroy', $this->piece->id));

        $this->assertDatabaseMissing('piece_tag', ['piece_id' => $piece_id]);
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
