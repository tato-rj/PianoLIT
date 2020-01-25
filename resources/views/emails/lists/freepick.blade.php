@component('mail::message', ['subscription' => $subscription, 'list' => $list])
<h1 style="color: #3D4852;
    font-size: 34px;
    font-weight: bold;
    margin-top: -34px;
    margin-bottom: 16px;
    text-align: center;
    "><span class="text-blue">Free pick</span> of the week</h1>

<div style="width: 100%; background: url({{$piece->getBackground()}}); background-position: center; background-size: cover; position: relative;" class="mb-4">
	<div style="max-width: 60%; padding-left: 16px; padding-bottom: 16px; padding-top: 80px;">
		<h1 style="color: white; margin: 0; font-size: 24px">{{$piece->medium_name}}</h1>
		<p style="color: white; margin-bottom: 4px">by {{$piece->composer->name}}</p>
		<p style="color:white; font-size: 12px; margin: 0">
			<span style="width: 9px; height: 9px; display: inline-block; border-radius: 50%" class="bg-{{$piece->level->name}}"></span> {{strtoupper($piece->level->name)}}
		</p>
	</div>
</div>
<h1><strong>About this piece</strong></h1>
{{$piece->description}}

@component('mail::button', ['url' => config('app.stores.ios')])
Check this week's FREE pick
@endcomponent

<div style="width: 330px; margin: 0 auto; margin-bottom: 42px">
	<p style="margin-bottom: 6px">{{hex('check')}} <strong>Get the score</strong></p>
	<p style="margin-bottom: 6px">{{hex('check')}} <strong>Watch a video performance</strong></p>
	<p style="margin-bottom: 6px">{{hex('check')}} <strong>Watch {{count($piece->videosArrayRaw) - 1}} video tutorials</strong></p>
	<p style="margin-bottom: 6px">{{hex('check')}} <strong>Speed up/slow down the audio</strong></p>
	<p style="margin-bottom: 6px">{{hex('check')}} <strong>Discover similar pieces</strong></p>
	<p style="margin-bottom: 6px">{{hex('check')}} <strong>Find top performances on Apple Music</strong></p>
</div>

<div class="divider divider-vertical"></div>

@php($videos_count = 1)
@foreach($piece->videos_array as $tutorial)

<div class="badge badge-pill alert-yellow" style="margin-bottom: 6px">
	{{strtoupper($tutorial['title'])}} {{strtolower($tutorial['title']) == 'tutorial' ? $videos_count : null }}
</div>

<p style="margin-left: 4px">{{$tutorial['description']}}</p>

@php($videos_count += 1)
@endforeach

<div class="divider divider-vertical"></div>

@component('mail::panel')
<h1>Did you know?</h1>
{{$piece->curiosity}}
@endcomponent

@component('mail::promotion')
<h1 style="text-align: center"><strong>Learn more about the composer</strong></h1>
<img src="{{storage($piece->composer->cover_path)}}" style="width: 160px; border-radius: 50%; display: block; margin: 0 auto" class="mb-4">
{{$piece->composer->biography}}
@endcomponent

@component('mail::button', ['url' => config('app.stores.ios')])
Check this week's FREE pick
@endcomponent

Best,<br>
Elena from {{ config('app.name') }}
@endcomponent
