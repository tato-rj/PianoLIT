@php($share = \App\Support\LinkShare::forPage($url, $title))
<div class="link-share" data-link-share data-share-url="{{ $share['url'] }}" data-share-title="{{ $share['title'] }}" data-share-message="{{ $share['message'] }}">
@component('components.dialogs.modal', ['id' => $id, 'header' => 'Share', 'centered' => true])
@slot('body')
@include('components.media.link-share-options')
@endslot
@endcomponent
</div>
