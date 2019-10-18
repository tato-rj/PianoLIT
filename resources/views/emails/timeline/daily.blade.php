@component('mail::message')
# <img src="{{asset('images/icons/birthday-cake.png')}}" style="width: 20px"> Happy birthday to {{$composer->short_name}}!

{{$composer->gender == 'male' ? 'He' : 'She'}} was born {{now()->diffInYears($composer->date_of_birth)}} years ago on {{$composer->date_of_birth->toFormattedDateString()}}.

{{preview($composer->biography, 40)}}...

@component('mail::button', ['url' => wiki($composer->name)])
Learn more about {{$composer->last_name}}
@endcomponent

@if($history->count() > 0)
@include('emails.timeline.history')
@endif

@endcomponent
