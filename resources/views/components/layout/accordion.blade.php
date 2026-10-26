<div id="{{ $id }}" class="accordion {{ ($flush ?? true) ? 'accordion-flush' : '' }}" @if($preview ?? false) data-ds-preview @endif>
    {{ $slot }}
</div>
