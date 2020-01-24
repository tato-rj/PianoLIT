@component('mail::message', ['email' => $subscriber->email])
# Hi there!

Here is your gift, we hope you enjoy it:)

@component('mail::button', ['url' => $gift_url])
<img src="{{asset('images/emails/gift-white.png')}}" style="margin-right: 12px; width: 16px">We have a gift for you!
@endcomponent

Best<br>
Elena from {{ config('app.name') }}
@endcomponent

