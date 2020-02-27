@component('mail::message', ['subscription' => $subscription, 'list' => $list])
<h1 class="mail-title">Latest tutorials & tips</h1>
Here is quick recap of our latest tutorials and teaching tips! If you need help with any piece found in the app, just tap the <span class="text-blue"><strong>Request Tutorial</strong></span> button on the videos tab to submit your request.

<h1 class="text-center">Don't have the app yet? What are you waiting for?</h1>
@component('mail::button', ['url' => config('app.stores.ios')])
Download the iOS App now
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>Tango Romantico by T. Brown</small></strong>
</div>
<h1 class="text-center mb-2">Playing octaves in the lower register of the piano with a lean and clear sound</h1>
<div class="text-center">
	<div class="badge badge-pill alert-intermediate mb-2">{{strtoupper('intermediate')}}</div>
</div>
Playing octaves is an essential part of piano playing. The lower region of the piano offers specific challenges when it comes to playing octaves, and that has to do with resonance. Let's see some exercises to play clean octaves on the left hand by highlighting the thumb and playing the bottom notes much softer.
@endcomponent

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>Doctor Gradus Ad Parnassum L 113 by C. Debussy</small></strong>
</div>
<h1 class="text-center mb-2">Using gestures to shape your musical lines</h1>
<div class="text-center">
	<div class="badge badge-pill alert-advanced mb-2">{{strtoupper('advanced')}}</div>
</div>
Here we'll explore some ideas to practice the running notes on the right hand in the main theme of this piece. Most often, a technical challenge can be overcome with a simple musical gesture and, in this case, coordinating your fingers with the motion of your wrist is essential to get the flowing character this theme requires.
@endcomponent

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>The Goblin and the Mosquito by F. Price</small></strong>
</div>
<h1 class="text-center mb-2">Playing different musical ideas at the piano</h1>
<div class="text-center">
	<div class="badge badge-pill alert-intermediate mb-2">{{strtoupper('intermediate')}}</div>
</div>
Once we know what we want each character to sound like, the next question is: how do we do this? What tools or skills can we use to communicate the music we want? Here are some ideas on how to bring a musical idea to life and, by doing that, making your performance much more effective!
@endcomponent

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>A B C D E F G by D. Rahbee</small></strong>
</div>
<h1 class="text-center mb-2">The basics of two-part form and what we can do to highlight this structure</h1>
<div class="text-center">
	<div class="badge badge-pill alert-elementary mb-2">{{strtoupper('elementary')}}</div>
</div>
Most pieces we play at this level are in two-part form or, as it is also referred to, an A-B form. We often overlook these simple forms, but understanding them is crucial for a good performance. In this lesson, we'll talk about why knowing this matters and how we can leverage it to make the music more engaging and expressive.
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
