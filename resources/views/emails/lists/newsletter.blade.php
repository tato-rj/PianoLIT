@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'Works of art make rules; rules do not make works of art.',
	'author' => 'C. Debussy'
])

<p>What’s your resolution for the year 2021, aside from practicing more? Ours at PianoLIT is to keep discovering new piano pieces for you. If you have any questions or any piece you’d like to see in the app just hit reply and send us your request.</p>
<p>🎁 Are you a member? <a href="https://pianolit.com/ebooks/music-intervals" target="_blank">Our latest eBook</a> explaining Music Intervals is <u>included with a membership plan</u>. Sign up now and get it for <strong>FREE</strong>!</p>

@component('mail::panel')
# How do you think we're doing?
If you’ve downloaded the PianoLIT app, please tell us what you think <a href="https://forms.gle/BLG89NvjmkWdwSVMA" target="_blank">HERE</a>! This helps us improve and provide you with the best tools for your musical journey.
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'eBook', 
		'color' => 'green',
		'link' => 'https://pianolit.com/ebooks/music-intervals'
	],
	'title' => 'Music Intervals'
])

<p>Musical intervals are the foundation of music theory. In order to understand how chords and harmony work, first you need to know how to count intervals between notes and understand their different types.</p>
<p>In this eBook, you will also learn what musical terms such as consonance, dissonance or harmonic series mean. You will also have a chance to test your knowledge with lots of exercises at the end!</p>
<p>🎁 Are you a member? Any eBook is included with a membership plan. Sign up now and get it for <strong>FREE</strong>!</p>

@component('mail::button', ['url' => 'https://pianolit.com/ebooks/music-intervals'])
Get eBook here
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'listen', 
		'color' => 'orange',
		'link' => 'https://youtu.be/rg_bezTHLaU'
	],
	'title' => 'The Leader of the Golden Turtles'
])

<p>“Histories” is a collection of 10 impressionistic short pieces for intermediate levels, an excellent choice for those starting to learn this genre.</p>

<p>The Leader of the Golden Turtles (La meneuse de tortues d'or) has a stately and noble character with broad sounds and gestures. The first theme is lighter with and almost improvisatory character, while the second theme is much more serious and confident. Both themes return at the end mixed together, and the challenge here is in playing them at the same time while maintaining their original character and articulation.</p>

<p><a href="https://youtu.be/rg_bezTHLaU" class="text-md"><strong>Watch “The Leader of the Golden Turtles” by J. Ibert</strong></a></p>

<p><a href="https://pianolit.com/storage/app/score/pianolit-1-la-meneuse-de-tortues-dor-in-d-minor-from-histories-3316.pdf" class="text-md"><strong>Get the score for FREE here

</strong></a></p>

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/blog/piano-pieces-by-black-composers'
	],
	'title' => 'Piano pieces by Black Composers'
])

<p>Let's take a quick look at some of the most influential black composers, their inspiring stories, and incredible music.</p>

@component('mail::button', ['url' => 'https://pianolit.com/blog/piano-pieces-by-black-composers'])
Check out this post
@endcomponent

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'crash course', 
		'color' => 'yellow',
		'link' => 'https://pianolit.com/crashcourses/5-hidden-gems-for-beginner-and-intermediate-pianists'
	],
	'title' => '5 hidden gems for beginner and intermediate pianists'
])

<p>Expand your knowledge in piano repertoire by diving deep into 5 remarkable pieces and the composers behind them.</p>

@component('mail::button', ['url' => 'https://pianolit.com/crashcourses/5-hidden-gems-for-beginner-and-intermediate-pianists'])
Start the FREE crash course today!
@endcomponent
@endcomponent

<h1 class="text-lg mb-4">True or False: Impressionist music</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>Debussy was an icon of the impressionist movement, although he did not define himself as an impressionist:</strong> TRUE | FALSE',
	'<strong>Maurice Ravel was another leading figures in impressionism:</strong> TRUE | FALSE',
	'<strong>György Ligeti is one of the main figure of late impressionism in music:</strong> TRUE | FALSE',
	'<strong>“Impressionism” is a term borrowed after one of Monet\'s paintings:</strong> TRUE | FALSE'
]])

<div class="text-center mt-4">Find the answers at the bottom of the newsletter!</div>

@include('mail::divider', ['orientation' => 'vertical'])

<p class="text-center">Don't have the iOS app? You can also access PianoLIT with any other device or web browser!</p>

@component('mail::button', ['url' => config('app.stores.webapp')])
Check out the PianoLIT WebApp
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

<h1 class="text-lg mb-4">COOL FACTS TO SHARE THIS MONTH:</h1>

@component('mail::panel')
Ever wondered how Mozart really looked like if you had the privilege to meet him in real life? Here’s the computer reconstruction of the artist’s bust by GC artist Hadi Karimi.

Read more <a href="https://www.artstation.com/artwork/J9z83Z" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
This commercial by Tosando Music went viral in Japan and inspired many adults to learn to play the piano at home.

Check this out <a href="https://www.youtube.com/watch?v=XnOWGVCUJw8" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Did you know? A RASTRUM is a multi-nibbed pen used to draw the five lines of a musical stave simultaneously. It literally means ‘rake’ in Latin.

Read more about it <a href="https://www.reddit.com/r/specializedtools/comments/f47j5j/a_rastrum_is_a_multinibbed_pen_used_to_draw_the/" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
To end with a good laugh! 😂

Rowan Atkinson performing the European Anthem in German...until he runs out of words at the end of the first verse.

Check it out <a href="https://www.youtube.com/watch?v=oWGZdYNpaSo" target="_blank">HERE</a>.
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. TRUE, 2. TRUE, 3. FALSE, 4. TRUE</div>

<p>Here at PianoLIT we are working non-stop to improve and expand our music library. If you wish to see the recording of any piece, please reply to this email with your request and we’ll jump on it. We’ve already received some great input (you’ll see them soon in the app!) and thanks to our users for having such great ideas.</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Download PianoLIT for iOS here
@endcomponent

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
