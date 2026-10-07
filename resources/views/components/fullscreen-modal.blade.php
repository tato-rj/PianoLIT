<div class="modal fade fullscreen-modal {{ $classes ?? '' }}" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $headingId }}" aria-hidden="true" @foreach($data ?? [] as $key => $value) data-{{ $key }}="{{ $value }}" @endforeach>
    <div class="modal-dialog modal-fullscreen"><div class="modal-content border-0 rounded-0">
        {{ $slot }}
    </div></div>
</div>
