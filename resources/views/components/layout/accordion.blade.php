<div id="{{ $id }}" class="accordion {{ ($flush ?? true) ? 'accordion-flush' : '' }} {{ ($variant ?? null) === 'minimal' ? 'accordion--minimal' : '' }}" @if($preview ?? false) data-ds-preview @endif>
    {{ $slot }}
</div>
