<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Performance;

class FileManagerWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        // Performance::byPublicId($request['public_id'])->first()->process($request['secure_url']);

        return response(200);
    }
}
