@component('mail::layout')
{{ $slot }}

@slot('footer')
@component('mail::footer')
{{ $footer ?? '' }}
@endcomponent
@endslot
@endcomponent
