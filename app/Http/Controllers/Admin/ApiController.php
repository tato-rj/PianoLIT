<?php

namespace App\Http\Controllers\Admin;

use App\Api\Api;
use App\Services\WebApp\PieceCards;
use App\Tag;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class ApiController extends Controller
{
    public function discover(Api $api, Request $request)
    {
        $key = Redis::get('app.discover');

        $collection = $api->for('webapp')->discover()->filter(function ($row) {
            return in_array($row['row'] ?? null, ['freepick', 'composers'], true);
        })->map(function ($row) {
            // Presentation attributes must not leak back into cached feeds.
            if ($row['row'] === 'freepick') {
                $row['content'] = PieceCards::load(collect($row['content'])->map(function ($piece) {
                    return clone $piece;
                }), false);
            }
            return $row;
        });

        return view('admin.pages.api.discover.index', compact(['collection', 'key']));
    }
}
