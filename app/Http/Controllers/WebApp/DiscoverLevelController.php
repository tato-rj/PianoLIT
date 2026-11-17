<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use App\Services\WebApp\{AccessPolicy, MatchTour};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiscoverLevelController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user('web');
        abort_unless(app(AccessPolicy::class)->available('personalization', $user), 403);
        $data = $request->validate(['playing_level' => ['present', 'nullable', 'string', Rule::in(array_keys(MatchTour::LEVELS))]]);
        if ($data['playing_level'] === null) {
            DB::table('discover_preferences')->where('user_id', $user->id)->delete();
        } else {
            DB::table('discover_preferences')->upsert([['user_id' => $user->id, 'playing_level' => $data['playing_level']]],
                ['user_id'], ['playing_level']);
        }
        return redirect()->to(route('webapp.discover').'#discover-playing-level')
            ->with('status', $data['playing_level'] ? 'Your playing level has been saved.' : 'Your playing level has been cleared.');
    }
}
