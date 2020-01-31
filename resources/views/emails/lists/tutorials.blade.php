@component('mail::message', ['subscription' => $subscription, 'list' => $list])
<h1 class="mail-title">This week's tutorials & tips</h1>
Here is quick recap of the tutorials we published this week! If you need help with any piece found in the app, just tap the <span class="text-blue"><strong>Request Tutorial</strong></span> button on the videos tab to submit your request.

<h1 class="text-center">Don't have the app yet? What are you waiting for?</h1>
@component('mail::button', ['url' => config('app.stores.ios')])
Download the iOS App now
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@php($tutorials = (new \App\Mail\Traits\Tutorials)->get())
@foreach($tutorials as $tutorial)
@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>{{$tutorial['piece']}}</small></strong>
</div>
<h1 class="text-center mb-2">{{$tutorial['title']}}</h1>
<div class="text-center">
	<div class="badge badge-pill alert-{{$tutorial['level']}} mb-2">{{strtoupper($tutorial['level'])}}</div>
</div>
{{$tutorial['description']}}
@endcomponent
@endforeach

@include('mail::divider', ['orientation' => 'vertical'])

<h1 class="text-lg">How can I find these videos in the app?</h1>
@include('mail::lists.numbered', ['items' => [
	'Open the <strong class="text-blue">APP</strong>',
	'Search for the piece on the <strong class="text-blue">SEARCH</strong> tab',
	'You\'ll find them under the <strong class="text-blue">VIDEO</strong> tab',
	'Enjoy!'
]])

@component('mail::button', ['url' => config('app.stores.ios')])
Open the APP
@endcomponent

<div class="divider divider-vertical"></div>

If you have any suggestions or special requests, don't hesitate to send us a note. We'll love to hear from you!

@include('mail::signature')
@endcomponent
