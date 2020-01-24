@component('mail::message', ['subscription' => $subscription, 'list' => $list])
# Welcome to our newsletter

Thank you for subscribing! We will send you notifications about our latests posts, app features and promotions about once per month. We will also let you know whenever there is a birthday of a famous composer!

We always strive to make our emails interesting and valuable to you. If you wish to unsubscribe at anytime just click on the link at the end of any email.

Best<br>
Elena from {{ config('app.name') }}
@endcomponent
