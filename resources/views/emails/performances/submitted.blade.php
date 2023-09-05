@component('mail::message')
# Hi there!

Your performance of <strong>{{$piece->long_name_with_composer}}</strong> has been submitted, we will let you know when it goes live.

Best<br>
Elena from {{ config('app.name') }}
@endcomponent