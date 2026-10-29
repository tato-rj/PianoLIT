@php($share = \App\Support\LinkShare::forPage($url, $title))
<div class="link-share" data-link-share data-share-url="{{ $share['url'] }}" data-share-title="{{ $share['title'] }}" data-share-message="{{ $share['message'] }}">
@component('components.dialogs.modal', ['id' => $id, 'header' => 'Share', 'centered' => true])
@slot('body')
<div class="link-share__targets" aria-label="Share using">
    @foreach($share['destinations'] as $destination)
    <a class="link-share__target link-share__target--{{ $destination['brand'] ?? $destination['icon'] }} btn-raw link-none" href="{{ $destination['url'] }}" @if(isset($destination['sms'])) data-share-sms @endif @if(strpos($destination['url'], 'https:') === 0) target="_blank" rel="noopener noreferrer" @endif aria-label="Share via {{ $destination['label'] }}">
        <span class="link-share__icon" aria-hidden="true">
            @isset($destination['brand'])
            @fa(['fa_type' => 'b', 'icon' => $destination['brand'], 'mr' => 0])
            @else
            @icon($destination['icon'], ['mr' => 0])
            @endisset
        </span>
        <span>{{ $destination['label'] }}</span>
    </a>
    @endforeach
</div>
<div class="link-share__copy">
    @input(['id' => $id.'-url', 'name' => 'share_url', 'label' => 'Page link', 'value' => $share['url'], 'readonly' => true, 'useOldInput' => false, 'required' => '', 'classes' => 'pe-5'])
    <button type="button" class="btn-raw text-body link-share__copy-button" data-share-copy aria-label="Copy page link">
        @icon('copy', ['mr' => 0])
    </button>
</div>
<div aria-live="polite">
    <span class="visually-hidden" data-share-status role="status"></span>
    <div data-share-error hidden>@alert(['color' => 'red', 'message' => '<span data-share-error-message>Select the page link and copy it.</span>', 'fullX' => true])</div>
</div>
@endslot
@endcomponent
</div>
