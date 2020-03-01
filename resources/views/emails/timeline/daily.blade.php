@component('mail::message', ['subscription' => $subscription, 'list' => $list])
<div style="margin-top: -1.5em; margin-bottom: 2em;">
	<div style="text-align: center; font-weight: 18px; margin-bottom: .3em">{{implode('', emoji('birthday', 4))}}</div>
	<div class="text-lg text-center" style="color: #3D4852; font-weight: bold;">Happy birthday to {{$composer->short_name}}!
	</div>
</div>

@include('emails.timeline.avatar')

<div style="text-align: center; margin-bottom: 1.5em">
	<div style="font-size: 16px">{{$composer->gender == 'male' ? 'He' : 'She'}} was born {{now()->diffInYears($composer->date_of_birth)}} years ago on</div>
	<div style="color: #4dc0b5; font-size: 30px; font-weight: bold">{{$composer->date_of_birth->format('F jS, Y')}}</div>
</div>

@component('mail::promotion')
<h1 class="text-lg text-center" style="margin-bottom: 4px">Did you know?</h1>
<div class="text-center">{{$composer->curiosity}}</div>
@endcomponent

<h2 class="text-center">Download <span class="text-blue">PianoLIT</span> app to find unique pieces by this great composer!</h2>

@component('mail::button', ['url' => config('app.stores.ios')])
Discover pieces by {{$composer->short_name}}
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@if($history->count() > 0)
@include('emails.timeline.history')
@endif

@endcomponent
