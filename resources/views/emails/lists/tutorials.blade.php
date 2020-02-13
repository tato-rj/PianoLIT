@component('mail::message', ['subscription' => $subscription, 'list' => $list])
<h1 class="mail-title">This week's tutorials & tips</h1>
Here is quick recap of the tutorials we published this week! If you need help with any piece found in the app, just tap the <span class="text-blue"><strong>Request Tutorial</strong></span> button on the videos tab to submit your request.

<h1 class="text-center">Don't have the app yet? What are you waiting for?</h1>
@component('mail::button', ['url' => config('app.stores.ios')])
Download the iOS App now
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>III. Presto from Sonata Op.27 No.2 "Moonlight" by L.V. Beethoven</small></strong>
</div>
<h1 class="text-center mb-2">How to play fast repeated notes without accumulating tension on your wrists</h1>
<div class="text-center">
	<div class="badge badge-pill alert-advanced mb-2">{{strtoupper('advanced')}}</div>
</div>
The stormy final movement is in unexpected and powerful contrast to the sonata's more intimate beginning. In this tutorial, we explore the last theme of the third movement, where both hands play fast repeated chords. This is something that every pianist struggles with as it easily accumulates tension making your arm and wrists tired. We talk in detail how to practice this part to develop accuracy and avoid stiff movements.
@endcomponent

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>Autumn sketch from 24 Lyric Preludes in Romantic Style by W. Gillock</small></strong>
</div>
<h1 class="text-center mb-2">How to balance the sound between your hands</h1>
<div class="text-center">
	<div class="badge badge-pill alert-intermediate mb-2">{{strtoupper('intermediate')}}</div>
</div>
Using this romantic piece by Gillock, we analyze the melodic lines and how to improve on them by balancing the sound between hands, carefully discerning the sound produced by your right hand (bearer of the melody) from the one produced by your left hand (accompaniment).
@endcomponent

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>The bear from Op.69 No.3 by D. Shostakovich</small></strong>
</div>
<h1 class="text-center mb-2">Polishing repeated notes and improve the accuracy of your jumps with the "stop and prepare" technique</h1>
<div class="text-center">
	<div class="badge badge-pill alert-beginner mb-2">{{strtoupper('beginner')}}</div>
</div>
In this tutorial, we review a couple of fundamental tips: polishing repeated notes and jumps accuracy. Let's learn how to improve your musical phrase when playing repeated notes by avoiding an overly percussive sound. Review the "stop and prepare" technique and understand how it can help us perform this piece fluently.
@endcomponent

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>Short Story by H. Lichner</small></strong>
</div>
<h1 class="text-center mb-2">Learn how to play full scales with even and clean sound</h1>
<div class="text-center">
	<div class="badge badge-pill alert-beginner mb-2">{{strtoupper('beginner')}}</div>
</div>
We use this beginner piece to talk about a common topic in piano playing: scales. And specifically one of the fundamentals about scales, which is keeping an even and clean sound during the ascending and descending motion. We review the basics regarding the passage of the thumb and a good exercise for thumb mobility.
@endcomponent

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
