<?php

namespace Tests\Traits;

use Illuminate\Http\UploadedFile;
use App\Piece;

trait ManageDatabase
{
	public function postPiece()
	{
        $file = UploadedFile::fake()->create('file.txt');

        $model = make(Piece::class);

        $piece = [
            'name' => $model->name,
            'nickname' => $model->nickname,
            'collection_name' => $model->collection_name,
            'collection_number' => $model->collection_number,
            'catalogue_name' => $model->catalogue_name,
            'catalogue_number' => $model->catalogue_number,
            'movement_number' => $model->movement_number,
            'curiosity' => $model->curiosity,
            'videos' => null,
            'itunes' => null,
            'key' => $model->key,
            'score_url' => $model->score_url,
            'score_editor' => $model->score_editor,
            'score_publisher' => $model->score_publisher,
            'score_copyright' => $model->score_copyright,
            'composer_id' => $model->composer_id,
            'composed_in' => $model->composed_in,
            'period' => [1],
            'length' => [2],
            'level' => [3],
            'audio' => $file,
            'audio_rh' => $file,
            'audio_lh' => $file,
            'score' => $file,
        ];

        $this->post(route('admin.pieces.store'), $piece);

        return Piece::latest()->first();
	}

    protected function updatePiece($piece, $attr = [])
    {
        $model = make(Piece::class);

        $updatedPiece = [
            'name' => $model->name,
            'nickname' => $model->nickname,
            'collection_name' => $model->collection_name,
            'collection_number' => $model->collection_number,
            'catalogue_name' => $model->catalogue_name,
            'catalogue_number' => $model->catalogue_number,
            'movement_number' => $model->movement_number,
            'curiosity' => $model->curiosity,
            'videos' => null,
            'itunes' => null,
            'key' => $model->key,
            'score_url' => $model->score_url,
            'score_editor' => $model->score_editor,
            'score_publisher' => $model->score_publisher,
            'score_copyright' => $model->score_copyright,
            'composer_id' => $model->composer_id,
            'composed_in' => $model->composed_in,
            'period' => [1],
            'length' => [2],
            'level' => [3],
            'audio' => UploadedFile::fake()->create('new-audio.mp3'),
            'score' => UploadedFile::fake()->create('new-score.pdf'),
        ];
        
        $this->patch(route('admin.pieces.update', $piece->id), array_merge($updatedPiece, $attr));

        return $updatedPiece;
    }

    protected function register($user = null, $bot = null)
    {
        $request = [
            'first_name' => $user['first_name'] ?? 'John',
            'last_name' => $user['last_name'] ?? 'Doe',
            'email' => $user['email'] ?? 'doe@email.com',
            'password' => $user['password'] ?? 'secret999',
            'password_confirmation' => $user['password_confirmation'] ?? 'secret999',
            'locale' => $user['locale'] ?? 'en_US',
            'age_range' => $user['age_range'] ?? '35 to 45',
            'experience' => $user['experience'] ?? 'Little',
            'preferred_piece_id' => $user['preferred_piece_id'] ?? create('App\Piece')->id,
            'occupation' => $user['occupation'] ?? 'Teacher',
            'origin' => 'web',
            'g-recaptcha-response' => 'token',
        ];

        if ($bot)
            $request = $request + ['g-recaptcha-response' => 'test'];

        return $this->post(route('api.users.store'), $request);     
    }
}
