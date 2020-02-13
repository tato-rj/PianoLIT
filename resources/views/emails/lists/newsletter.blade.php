@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', ['quote' => 'I live in music like a fish in water.', 'author' => 'Camille Saint-Saëns'])

As many of you already know, we launched the PianoLIT app on January 17th. We appreciate your enthusiasm and hope your experience will keep improving while we'll do our best to serve you the greatest and rarest piano gems out there! Stay tuned as more tutorials and free picks are on their way!

@component('mail::newsletter.section', [
	'badge' => ['name' => 'watch', 'color' => 'orange'],
	'title' => 'Rising with music'])

<p>This piece by C. Chaminade is perfect for a late-intermediate/early-advanced romantic level. It has a reflective and dreamy quality to it that will make you fall in love right away. The upwards motion of the melody perfectly reflect its title. <a href="">Get the score here</a>  👈</p>

<p>The level of this piece is equivalent to <span class="text-orange"><strong>ABRSM 8</strong></span> - <span class="text-orange"><strong>RCM 9</strong></span></p>

<p><a href="" style="font-size: 108%"><strong>Élévation from 6 Songs Without Words Op. 76 by C. Chaminade, watch on YouTube</strong></a></p>

<p style="font-size: 108%"><i><strong>FUN FACT</strong>: In 1913, Chaminade was awarded the Légion d'Honneur, a first for a female composer.</i></p>

@include('mail::divider', ['orientation' => 'vertical'])
@endcomponent

@endcomponent
