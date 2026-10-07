@php($campaignAttribution = app(\App\Services\CampaignAttribution::class))
@if($campaignAttribution->available(request()))
    @php($campaignToken = $campaignAttribution->landingToken(request()))
    @php($campaignConsent = $campaignAttribution->consent(request()))
    <aside class="container my-3" data-campaign-measurement
        data-url="{{ route('webapp.attribution.consent') }}" data-csrf="{{ csrf_token() }}"
        data-token="{{ $campaignToken }}" data-consent="{{ $campaignConsent }}">
        <button type="button" class="btn btn-link btn-sm" data-campaign-settings aria-expanded="{{ $campaignToken && !$campaignConsent ? 'true' : 'false' }}" aria-controls="campaign-measurement-choice">Video link measurement</button>
        <div id="campaign-measurement-choice" class="border rounded p-3" @if(!$campaignToken || $campaignConsent) hidden @endif>
            <p class="mb-2">May PianoLIT remember this video link for 30 days and connect it to your trial and first subscription payment? This optional measurement helps us understand which videos bring listeners here. Choosing No thanks will not affect your account or access.</p>
            <p class="small text-muted">You can change this choice here. Withdrawing removes your campaign link. This choice covers only PianoLIT’s video link measurement.</p>
            <button type="button" class="btn btn-default btn-sm" data-campaign-choice="granted">Allow measurement</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-campaign-choice="denied">No thanks / withdraw</button>
        </div>
        <p class="small mb-0" role="status" aria-live="polite" data-campaign-status></p>
    </aside>
    <script src="{{ mix('js/views/campaign-attribution.js') }}" defer></script>
@endif
