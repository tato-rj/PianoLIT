@component('mail::message', ['email' => $subscriber->email])
# Hi there!

Here is the gift you, we hope you enjoy it:)

@component('mail::button', ['url' => route('gift', ['gift' => $gift])])
<img src="{{asset('images/emails/gift-white.png')}}" style="margin-right: 12px; width: 16px">We have a gift for you!
@endcomponent

Thanks<br>
Elena from {{ config('app.name') }}
@endcomponent

