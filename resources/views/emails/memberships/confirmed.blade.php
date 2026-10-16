@component('mail::message')
# You’re all set!

Hi {{ $firstName }}, your PianoLIT Premium membership is active. Enjoy full access to scores, videos, and recordings. Your next favorite piece is waiting for you.

**Your plan:** {{ $plan }}

@if($trial)
Your free trial is active. No payment was due today.@if($renewalDate) Your first billing date is **{{ $renewalDate }}**. Cancel before then in My Membership to avoid the first payment.@endif
@elseif($renewalDate)
Your next renewal is **{{ $renewalDate }}**.
@endif

@component('mail::button', ['url' => route('webapp.discover')])
Start exploring
@endcomponent

You can view your billing details and manage your plan anytime in [My Membership]({{ route('webapp.membership.edit') }}).

Need a hand? Reply to this email or contact [contact@pianolit.com](mailto:contact@pianolit.com). We’re happy to help.

Enjoy the music,<br>
The PianoLIT team
@endcomponent
