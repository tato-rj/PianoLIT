@component('mail::message', ['email' => $subscriber->email])
# Hi there!

This is just a test.

Best<br>
Elena from {{ config('app.name') }}
@endcomponent

