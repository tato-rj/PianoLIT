@component('mail::layout-simple')
    {{-- Header --}}
    @slot('header')

    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Footer --}}
    @slot('footer')

    @endslot
@endcomponent
