<?php

namespace Tests\Traits;

use Illuminate\Http\UploadedFile;
use App\Piece;

trait ManageDatabase
{
	public function postPiece()
	{
        $file = UploadedFile::fake()->create('file.txt');

        $piece = make(Piece::class)->toArray();

        $piece = array_merge($piece, [
            'period' => [1],
            'length' => [2],
            'level' => [3],
            'audio_path' => $file,
            'audio_path_rh' => $file,
            'audio_path_lh' => $file,
            'score_path' => $file,
        ]);

        $this->post(route('admin.pieces.store'), $piece);

        return Piece::latest()->first();
	}

    protected function register($user = null)
    {
        return $this->post(route('api.users.store'), [
            'first_name' => $user['first_name'] ?? 'John',
            'last_name' => $user['last_name'] ?? 'Doe',
            'email' => $user['email'] ?? 'doe@email.com',
            'password' => $user['password'] ?? 'secret',
            'password_confirmation' => $user['password_confirmation'] ?? 'secret',
            'locale' => $user['locale'] ?? 'en_US',
            'age_range' => $user['age_range'] ?? '35 to 45',
            'experience' => $user['experience'] ?? 'Little',
            'preferred_piece_id' => $user['preferred_piece_id'] ?? create('App\Piece')->id,
            'occupation' => $user['occupation'] ?? 'Teacher',
        ]);     
    }
}
