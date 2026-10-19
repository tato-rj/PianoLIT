<div class="admin-page-loading{{ ($preview ?? false) ? ' admin-page-loading--preview' : '' }}" @if($preview ?? false) data-ds-preview aria-hidden="true" @else data-admin-page-loading role="progressbar" aria-label="Loading page" hidden @endif>
    <span class="admin-page-loading__bar"></span>
</div>
@unless($preview ?? false)
    {{-- Start before the page content and main bundle arrive. --}}
    <script>{!! file_get_contents(resource_path('js/components/admin-page-loading.js')) !!}</script>
@endunless
