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

@include('mail::lists.check', ['items' => [
	'Get the score', 
	'Watch a video performance',
	'Watch ' . (count($piece->videosArrayRaw) - 1) . ' video tutorials',
	'Speed up/slow down the audio',
	'Discover similar pieces',
	'Find top performances on Apple Music'
]])

@include('mail::divider', ['orientation' => 'vertical'])

@include('mail::lists.badge', ['tutorials' => $piece->videos_array])

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::panel')
<h1>Did you know?</h1>
{{$piece->curiosity}}
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

@include('mail::signature')
@endcomponent
