<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use App\Services\CampaignAttribution;
use Illuminate\Http\Request;

class CampaignAttributionController extends Controller
{
    public function consent(Request $request, CampaignAttribution $attribution)
    {
        // Retiring new collection must not prevent an existing visitor from
        // withdrawing their old permission and removing owned attribution.
        if (!$attribution->available($request)
            && ($request->input('choice') !== 'denied' || !$attribution->supportsRequest($request))) {
            return response()->json(['message' => 'Campaign measurement unavailable.'], 404);
        }
        $data = $request->validate(['choice' => 'required|in:granted,denied', 'token' => 'nullable|string|max:4096',
            'client_id' => ['nullable', 'string', 'regex:/^\d{1,20}\.\d{1,20}$/D'],
            'session_id' => ['nullable', 'regex:/^\d{1,12}$/D']]);
        $campaign = null;
        if ($data['choice'] === 'granted' && ! empty($data['token'])) {
            $campaign = $attribution->campaignFromToken($data['token']);
            abort_unless($campaign, 422, 'Please reload the campaign link before allowing measurement.');
        }
        $attribution->choose($request, $data['choice'], $campaign);
        if ($data['choice'] === 'granted') $attribution->captureGoogle($request, $data);
        return response()->json(['choice' => $data['choice']]);
    }
}
