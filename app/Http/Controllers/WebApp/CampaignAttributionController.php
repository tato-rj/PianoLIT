<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use App\Services\CampaignAttribution;
use Illuminate\Http\Request;

class CampaignAttributionController extends Controller
{
    public function consent(Request $request, CampaignAttribution $attribution)
    {
        if (! $attribution->available($request)) return response()->json(['message' => 'Campaign measurement unavailable.'], 404);
        $data = $request->validate(['choice' => 'required|in:granted,denied', 'token' => 'nullable|string|max:2048']);
        $campaign = null;
        if ($data['choice'] === 'granted' && ! empty($data['token'])) {
            $campaign = $attribution->campaignFromToken($data['token']);
            abort_unless($campaign, 422, 'Please reload the video link before allowing measurement.');
        }
        $attribution->choose($request, $data['choice'], $campaign);
        return response()->json(['choice' => $data['choice']]);
    }
}
