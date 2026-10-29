@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => \App\Support\EmailLayout::websiteUrl()])
@component('mail::editorial.logo', ['websiteUrl' => \App\Support\EmailLayout::websiteUrl()])
@endcomponent
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::editorial.customer-footer', \App\Support\EmailLayout::footer(get_defined_vars()))
@endcomponent
@endslot
@endcomponent
