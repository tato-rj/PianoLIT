@php($campaignAttribution = app(\App\Services\CampaignAttribution::class))
@if($campaignAttribution->available(request()))
    @php($campaignToken = $campaignAttribution->landingToken(request()))
    @php($campaignConsent = $campaignAttribution->consent(request()))
    @if($campaignToken || $campaignConsent === 'granted')
    <span hidden data-campaign-measurement
        data-url="{{ route('attribution.consent', [], false) }}" data-csrf="{{ csrf_token() }}"
        data-token="{{ $campaignToken }}" data-consent="{{ $campaignConsent }}"
        data-measurement-id="{{ \App\Analytics\WebsiteTracking::enabled(request()) ? config('services.google_analytics.measurement_id') : '' }}"></span>
    @if($campaignConsent === 'granted')
    <script src="{{ mix('js/views/campaign-attribution.js') }}" defer></script>
    @endif
    @endif
@endif
