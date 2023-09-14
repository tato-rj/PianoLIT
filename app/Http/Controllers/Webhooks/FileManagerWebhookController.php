<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Performance;

class FileManagerWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->ip() != env('FILEMANAGER_IP'))
            abort(404, 'You cannot make this request');

        if ($request->isMethod('POST'))
            return $this->process($request->video);

        if ($request->isMethod('DELETE'))
            return $this->delete($request->video);

        return abort(404, 'You cannot make this request');
    }

    public function process($video)
    {
        \Log::debug('Notification received from FileManager to PROCESS video ID ' . $video['id']);

        Performance::process($video);

        return response(200);
    }

    public function delete($video)
    {
        \Log::debug('Notification received from FileManager to DELETE a performance from User #' . $video['user_id'] . ' and Piece #'.$video['piece_id']);

        Performance::fromFileManager($video)->delete();

        return response(200);
    }
}
