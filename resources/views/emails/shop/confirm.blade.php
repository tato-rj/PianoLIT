@component('mail::message')
# Thank you for your purchase!

Hi {{$user->first_name}}, your purchase of <strong>{{$item->title}}</strong> has been confirmed and you can find your product by clicking on the button below.

@component('mail::button', ['url' => route('users.purchases')])
View product here
@endcomponent

If you have any questions just let us know, we're always here to help!

Best,<br>
{{ config('app.name') }}
@endcomponent
