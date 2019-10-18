<div style="text-align: center; position: relative; width: 80px; display: block; margin: 0 auto; margin-bottom: 1rem;">
	<img src="{{asset('images/icons/birthday-cake.png')}}" style="padding: .5rem;">
	<div style="background-color: #48a59d; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; justify-content: center; align-items: center; position: absolute; bottom: -10px; right: 0; font-size: 1.2em;">
		<strong>{{$composersBorn->count()}}</strong>
	</div>
</div>
@component('mail::panel')
@foreach($composersBorn as $composer)
<div style="{{$loop->last ? null : 'border-bottom: 1px solid #d5dade; margin-bottom: .5rem; padding-bottom: .5rem'}}">
	<strong>{{$composer->name}}</strong> was born {{now()->diffInYears($composer->date_of_birth) . ' years ago on ' . $composer->date_of_birth->toFormattedDateString() . '.'}}
</div>
@endforeach
@endcomponent