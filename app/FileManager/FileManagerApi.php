<?php

namespace App\FileManager;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use App\{Piece, Performance};

class FileManagerApi
{
	protected $piece;

	public function piece(Piece $piece)
	{
		$this->piece = $piece;

		return $this;
	}

	public function upload(UploadedFile $file)
	{
        $this->response = Http::acceptJson()->attach(
            'video', file_get_contents($file), $file->getClientOriginalName()
        )->post(env('FILEMANAGER_UPLOAD_URL'), [
            'secret' => env('FILEMANAGER_SECRET'),
            'origin' => 'webapp',
            'piece_id' => $this->piece->id,
            'email' => auth()->user()->email,
            'user_id' => auth()->user()->id
        ]);

        return $this;
	}

	public function delete(Performance $performance)
	{
        return Http::acceptJson()->delete(env('FILEMANAGER_DELETE_URL'), [
            'secret' => env('FILEMANAGER_SECRET'),
            'piece_id' => $performance->piece_id,
            'user_id' => $performance->user_id
        ]);
	}

	public function getResponse()
	{
		return $this->response;
	}
}