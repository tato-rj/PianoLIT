@component('mail::message')
# Congratulations!

Your performance of <strong>{{$piece->long_name_with_composer}}</strong> has been approved.

Best<br>
Elena from {{ config('app.name') }}
@endcomponent