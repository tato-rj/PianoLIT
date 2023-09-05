@component('mail::message')
# Hi there!

Your performance of {{$piece->long_name_with_composer}} has been submitted, we will let you know when it goes live.

Best<br>
Elena from {{ config('app.name') }}
@endcomponent