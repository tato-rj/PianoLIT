@component('mail::message')
# Hi there!

{{$user->first_name}} just sent you {{$piece->medium_name_with_composer}}. Click on the button below to open it.

@component('mail::button', ['url' => route('webapp.pieces.show', $piece)])
<img src="{{asset('images/emails/gift-white.png')}}" style="margin-right: 12px; width: 16px">Check out the piece
@endcomponent

Best<br>
Elena from {{ config('app.name') }}
@endcomponent
