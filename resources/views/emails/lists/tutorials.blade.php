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
	<strong><small>Revolutionary Étude by F. Chopin</small></strong>
</div>
<h1 class="text-center mb-2">Using gestures to improve rhythm and give a natural shape to musical lines</h1>
<div class="text-center">
	<div class="badge badge-pill alert-advanced mb-2">{{strtoupper('advanced')}}</div>
</div>
Chopin's Revoultionary Étude is an extraordiary musical and technical exercise for the left hand. Let's look at the main elements Chopin uses throughout this piece, explore different strategies to tackle the technical challenges and learn how we can use musical gestures to make this music come to life.
@endcomponent

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>Tambourin by Elisabetta de Gambarini</small></strong>
</div>
<h1 class="text-center mb-2">Explore the harmony and how it creates musical humor</h1>
<div class="text-center">
	<div class="badge badge-pill alert-intermediate mb-2">{{strtoupper('early intermediate')}}</div>
</div>
Gambarini lived during the transition between the Baroque and Classical periods and, like other composers of her generation, played with harmony to bring humor into her music. In this video we explore just that!
@endcomponent

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>Gavotte en Rondeau by J.S. Bach</small></strong>
</div>
<h1 class="text-center mb-2">The basics of rondo-form you need to know and why it matters</h1>
<div class="text-center">
	<div class="badge badge-pill alert-beginner mb-2">{{strtoupper('late beginner')}}</div>
</div>
Rondos were very common througout the Baroque and Classical periods. This genre is all about contrast between the main recurring theme and each episode. Learn how you can find in the music all the clues you need to understand just how contrasting each episode can be.
@endcomponent

@component('mail::panel')
<div class="text-center mb-2 pb-2 border-bottom">
	<strong><small>"Minute" Waltz by F. Chopin</small></strong>
</div>
<h1 class="text-center mb-2">How to play the left hand accompaniment, keeping it light and well controlled</h1>
<div class="text-center">
	<div class="badge badge-pill alert-intermediate mb-2">{{strtoupper('late intermediate')}}</div>
</div>
The left hand you will find in this piece is made up of a very common pattern in romantic music. Learning how to coordinate and controll it well is the key to playing this type of music well. Here are some exercises to help you loosen up the left hand and play this piece like a pro!
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
