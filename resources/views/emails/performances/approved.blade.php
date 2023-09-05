@component('mail::message')
# Congratulations!

Your performance of {{$piece->long_name_with_composer}} has been approved.

Best<br>
Elena from {{ config('app.name') }}
@endcomponent