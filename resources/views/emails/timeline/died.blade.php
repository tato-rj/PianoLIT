@component('mail::panel')
@foreach($composersDied as $composer)
<strong>{{$composer->name}}</strong> died {{now()->diffInYears($composer->date_of_death) . ' years ago on ' . $composer->date_of_death->toFormattedDateString() . '.'}}
<div style="{{$loop->last ? null : 'border-bottom: 1px solid #d5dade; margin-bottom: .5rem; padding-bottom: .5rem'}}"></div>
@endforeach
@endcomponent