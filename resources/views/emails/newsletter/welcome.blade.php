@component('mail::message')
# Welcome to our newsletter

Thank you for subscribing! We will send you notifications about our latests posts, app features and promotions about once per month. We always strive to make our emails interesting and valuable to you. If you wish to unsubscribe at anytime just click on the link at the end of any email.

@component('mail::button', ['url' => route('gift', 'circle-of-fifths-poster.pdf')])
<img src="{{asset('images/emails/gift-white.png')}}" style="margin-right: 12px; width: 16px">We have a gift for you!
@endcomponent

Thanks<br>
Elena from {{ config('app.name') }}
@endcomponent
