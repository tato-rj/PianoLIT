@component('mail::message', \App\Support\EmailLayout::footer(get_defined_vars()))
{{ $slot }}
@endcomponent
