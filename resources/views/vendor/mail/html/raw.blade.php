@component('mail::layout-simple')
@slot('header')
@endslot
{{ $slot }}
@slot('footer')
@endslot
@endcomponent
