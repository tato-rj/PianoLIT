@component('mail::message', ['subscription' => $subscription, 'list' => $list])
<h1 class="mail-title"><span class="text-blue">Free</span> pick of the week</h1>

@component('mail::highlights.cover', ['background' => $piece->getBackground()])
<h1 class="text-white m-0 text-lg">{{$piece->medium_name}}</h1>
<p class="text-white mb-1">by {{$piece->composer->name}}</p>
<p class="text-white m-0 text-sm">
	<span class="dot bg-{{$piece->level->name}}"></span> {{strtoupper($piece->extended_level_name)}}
</p>
@endcomponent

<h1 style="margin-bottom: 0"><strong>About this piece</strong></h1>
<p style="white-space: pre-wrap;">
{{$piece->description}}
</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Check this week's FREE pick
@endcomponent

@component('mail::panel')
<h1><strong>Don't use an iPhone?</strong></h1>
<p style="margin-bottom: 4px">You can also access PianoLIT from any browser! 🤗</p>
<p style="margin-bottom: 12px">Available on all devices, tablets and computers.</p>
<a href="https://my.pianolit.com/" target="_blank" class="text-center"><strong>Check the FREE pick on the WebApp</strong></a>
@endcomponent


@include('mail::lists.check', ['items' => [
	'Get the score', 
	'Watch a video performance',
	'Watch video tutorials',
	'Speed up/slow down the audio',
	'Discover similar pieces',
	'Find top performances on Apple Music'
]])

{{-- @include('mail::divider', ['orientation' => 'vertical']) --}}

{{-- @include('mail::lists.badge', ['tutorials' => $tutorials]) --}}

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::panel')
<h1>Did you know?</h1>
{{$piece->curiosity ?? $piece->composer->curiosity}}
@endcomponent

@component('mail::promotion')
<h1 class="text-center"><strong>Learn more about the composer</strong></h1>
@include('mail::avatar', ['image' => storage($piece->composer->cover_path)])

<p style="white-space: pre-wrap;">
{{$piece->composer->biography}}
</p>
@endcomponent

@component('mail::button', ['url' => config('app.stores.ios')])
Check this week's FREE pick
@endcomponent

@component('mail::panel')
<h1><strong>Don't use an iPhone?</strong></h1>
<p style="margin-bottom: 4px">Our WebApp is up and running! 🤗</p>
<p style="margin-bottom: 12px">Available on all devices, tablets and computers.</p>
<a href="https://my.pianolit.com/" target="_blank" class="text-center"><strong>Check the FREE pick on the WebApp</strong></a>
@endcomponent

@include('mail::signature')
@endcomponent
