@component('mail::message', ['email' => $subscriber->email])
<div style="margin-top: -1.5em; margin-bottom: 2em;">
	<div style="text-align: center; font-weight: 18px; margin-bottom: .3em">🎉👏🤗🎁</div>
	<div style="
		
		color: #3D4852;
	    font-size: 24px;
	    font-weight: bold; text-align: center;">Happy birthday to {{$composer->short_name}}!
	</div>
</div>

@include('emails.timeline.avatar')

<div style="text-align: center; margin-bottom: 1.5em">
	<div style="font-size: 16px">{{$composer->gender == 'male' ? 'He' : 'She'}} was born {{now()->diffInYears($composer->date_of_birth)}} years ago on</div>
	<div style="color: #4dc0b5; font-size: 30px; font-weight: bold">{{$composer->date_of_birth->format('F jS, Y')}}</div>
</div>

{{preview($composer->biography, 40)}}...

@component('mail::button', ['url' => wiki($composer->name)])
Learn more about {{$composer->last_name}}
@endcomponent

@if($history->count() > 0)
@include('emails.timeline.history')
@endif

@endcomponent
