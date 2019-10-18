<div style="text-align: center; position: relative; width: 80px; display: block; margin: 0 auto; margin-bottom: 1rem;">
	<img src="{{asset('images/icons/reaper.png')}}" style="padding: .5rem;">
	<div style="background-color: #697a8c; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; justify-content: center; align-items: center; position: absolute; bottom: -10px; right: 0; font-size: 1.2em;">
		<strong>{{$composersDied->count()}}</strong>
	</div>
</div>
@component('mail::panel')
@foreach($composersDied as $composer)
<div style="border-bottom: {{$loop->last ? null : '1px solid'}}">
	<strong>{{$composer->name}}</strong> died {{now()->diffInYears($composer->date_of_death) . ' years ago on ' . $composer->date_of_death->toFormattedDateString() . '.'}}
</div>
@endforeach
@endcomponent