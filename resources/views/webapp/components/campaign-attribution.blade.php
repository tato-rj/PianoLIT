@php($campaignAttribution = app(\App\Services\CampaignAttribution::class))
@if($campaignAttribution->available(request()))
    @php($campaignToken = $campaignAttribution->landingToken(request()))
    @php($campaignConsent = $campaignAttribution->consent(request()))
    @php($campaignSearchPage = request()->routeIs('webapp.search.*'))
    @if(!$campaignSearchPage || $campaignToken)
    <aside class="container my-3" data-campaign-measurement
        @if($campaignSearchPage && $campaignConsent) hidden @endif
        data-url="{{ route('attribution.consent', [], false) }}" data-csrf="{{ csrf_token() }}"
        data-token="{{ $campaignToken }}" data-consent="{{ $campaignConsent }}">
        <button type="button" class="btn btn-link btn-sm" data-campaign-settings aria-expanded="{{ $campaignToken && !$campaignConsent ? 'true' : 'false' }}" aria-controls="campaign-measurement-choice">Campaign link measurement</button>
        <div id="campaign-measurement-choice" class="border rounded p-3" @if(!$campaignToken || $campaignConsent) hidden @endif>
            <p class="mb-2">PianoLIT counts tagged visits anonymously. May PianoLIT also remember the link for 30 days and connect it to your account, trial and first subscription payment? Choosing No thanks removes this visit from campaign reporting and will not affect your account or access.</p>
            <p class="small text-muted">You can change this choice here. Withdrawing removes your campaign link. This choice covers only PianoLIT’s campaign link measurement.</p>
            <button type="button" class="btn btn-primary btn-sm" data-campaign-choice="granted">Allow measurement</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-campaign-choice="denied">No thanks / withdraw</button>
        </div>
        <p class="small mb-0" role="status" aria-live="polite" data-campaign-status></p>
    </aside>
    <script src="{{ mix('js/views/campaign-attribution.js') }}" defer></script>
    @endif
@endif
