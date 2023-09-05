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

        Performance::process($request->video);

        return response(200);
    }
}
