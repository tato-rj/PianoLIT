<a href="{{ route('home') }}" class="project-watermark{{ ($preview ?? false) ? ' position-absolute' : '' }}" aria-label="PianoLIT home" @if($preview ?? false) data-ds-preview @endif>
    <img src="{{ asset(mix('images/brand/icon-white.svg')) }}" width="44" height="44" alt="" aria-hidden="true" draggable="false">
</a>
