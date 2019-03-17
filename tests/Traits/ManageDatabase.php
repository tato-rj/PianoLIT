<?php

namespace Tests\Traits;

use Illuminate\Http\UploadedFile;
use App\Piece;

trait ManageDatabase
{
	public function postPiece()
	{
        $piece = make(Piece::class)->toArray();

        $piece = array_merge($piece, [
            'period' => [1],
            'length' => [2],
            'level' => [3],
            'audio_path' => UploadedFile::fake()->create('audio.mp3'),
            'audio_path_rh' => UploadedFile::fake()->create('audio_rh.mp3'),
            'audio_path_lh' => UploadedFile::fake()->create('audio_lh.mp3'),
            'score_path' => UploadedFile::fake()->create('score.pdf'),
        ]);

        $this->post(route('admin.pieces.store'), $piece);

        return Piece::latest()->first();
	}
}
