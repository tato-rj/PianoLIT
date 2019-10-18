@component('mail::panel')
@foreach($composersBorn as $composer)
<strong>{{$composer->name}}</strong> was born {{now()->diffInYears($composer->date_of_birth) . ' years ago on ' . $composer->date_of_birth->toFormattedDateString() . '.'}}
<div style="{{$loop->last ? null : 'border-bottom: 1px solid #d5dade; margin-bottom: .5rem; padding-bottom: .5rem'}}"></div>
@endforeach
@endcomponent