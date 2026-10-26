<div class="modal fade fullscreen-modal {{ $classes ?? '' }}" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $headingId }}" aria-hidden="true" @foreach($data ?? [] as $key => $value) data-{{ $key }}="{{ $value }}" @endforeach>
    <div class="modal-dialog modal-fullscreen">
        @if(!empty($floatingClose))
            @include('components.dialogs.fullscreen.close', ['closeClasses' => 'fullscreen-modal-close--floating'])
        @endif
        <div class="modal-content border-0 rounded-0">
            {{ $slot }}
        </div>
    </div>
</div>
