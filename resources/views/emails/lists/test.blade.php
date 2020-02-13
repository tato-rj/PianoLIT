@component('mail::message', ['subscription' => $subscription, 'list' => $list])
# Hi there!

This is just a test.

Best<br>
Elena from {{ config('app.name') }}
@endcomponent
