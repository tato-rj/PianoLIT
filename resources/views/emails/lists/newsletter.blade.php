@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'Music expresses that which cannot be said and on which is impossible to remain silent',
	'author' => 'V. Hugo'
])

We’ve made it to August and while this month’s vacation are probably going to be at home, there’s more time to dedicate to piano practice. If you’re looking into new repertoire, we have some wonderful additions.

@component('mail::panel')
# How do you think we're doing?
If you’ve downloaded the PianoLIT app, please tell us what you think <a href="https://forms.gle/BLG89NvjmkWdwSVMA" target="_blank">here</a>! This helps us improve and provide you with the best tools for your musical journey.
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'listen', 
		'color' => 'green',
		'link' => 'https://www.youtube.com/watch?v=RPFURmmnEHs'
	],
	'title' => '👩 A musical pioneer of the 18th century'
])

<p>Maria Teresa Agnesi was an accomplished Italian composer. Not much survived of what she did for keyboard, but this sonata is testimony to her great talent and capabilities. Does it sound just like something CPE Bach would have written? The early decades of the Classical period were marked by vibrant, bold, and optimistic music. The writing was simple and the texture clean, providing incredible contrast to the music of the Baroque period.</p>

<p><a href="https://www.youtube.com/watch?v=RPFURmmnEHs" class="text-md"><strong>Sonata in F major by Maria Teresa Agnesi, watch on YouTube</strong></a></p>

<p><a href="https://my.pianolit.com/pieces/828#score" class="text-md"><strong>You'll find the score here</strong></a></p>

<p>This beautiful short sonata embodies the spirit of this generation and it will help you develop clean and even playing on the left hand, as well as nuanced scale passages on the right hand.</p>


@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'orange',
		'link' => 'https://pianolit.com/blog/6-inspiring-flashy-pieces-that-are-easy-to-learn'
	],
	'title' => '6 Inspiring flashy pieces that are easy to learn'
])

<p>Playing the piano is hard. While this statement is absolutely true, there are a number of pieces that are actually easy to learn but sound very impressive, especially (shhh) for those who don't know much about it.</p>

@component('mail::button', ['url' => 'https://pianolit.com/blog/6-inspiring-flashy-pieces-that-are-easy-to-learn'])
Read more about this
@endcomponent


{{-- <p><a href="https://pianolit.com/resources/infographs/johannes-brahms" class="text-md" target="_blank">Brahms infographic, click HERE</a></p> --}}
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'quiz', 
		'color' => 'yellow',
		'link' => 'https://pianolit.com/quizzes/instruments-and-sounds-of-a-symphonic-orchestra'
	],
	'title' => 'QUIZ: Instruments and sounds of a Symphonic Orchestra'
])

<p>Modern symphony orchestras can have up to about 100 instruments. That's a lot of instruments! Do you know their names? And what about their categories? Let's find out.</p>

@component('mail::button', ['url' => 'https://pianolit.com/quizzes/instruments-and-sounds-of-a-symphonic-orchestra'])
Let me try!
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'infographic', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/resources/infographs/the-evolution-of-the-piano-keyboard'
	],
	'title' => 'The evolution of the piano keyboard'
])

<p>The standard modern piano keyboard has 88 keys, but it hasn't always been like that! In this chart, we explore some of the most significant moments in the evolution of this amazing instrument.</p>

@component('mail::button', ['url' => 'https://pianolit.com/resources/infographs/the-evolution-of-the-piano-keyboard'])
Download the infographic for FREE!
@endcomponent
@endcomponent

<h1 class="text-lg mb-4">True or False: C.P.E. Bach edition</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>He was the nephew of Johann Sebastian Bach:</strong> TRUE | FALSE',
	'<strong>Solfeggietto in C minor is probably his most famous piece for keyboard:</strong> TRUE | FALSE',
	'<strong>He was not popular in his life-time and died without recognition:</strong> TRUE | FALSE',
	'<strong>Mozart said about C.P.E. Bach: “He is the father, we are the children.”:</strong> TRUE | FALSE'
]])

<div class="text-center mt-4">Find the answers at the bottom of the newsletter!</div>

@include('mail::divider', ['orientation' => 'vertical'])

<h1 class="text-lg mb-4">Important news worth sharing this month:</h1>

@component('mail::panel')
Leon Fleisher, the legendary pianist, died at age 92 in Baltimore. He was not only an incredible musician, but an example of resilience and perseverance. Unable to use his right hand, he performed pieces written for left hand only, conducted and taught. Years later, he made a triumphant two-handed comeback.

Read more <a href="https://www.npr.org/sections/deceptivecadence/2020/08/02/702978476/leon-fleisher-the-pianist-who-reinvented-himself-dies-at-92" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
The great pianist Liszt had to travel for his numerous concerts and practice while on the road. Check out his silent piano, for practicing when he was on tour! Pretty smart! Would you get one?

Check it out <a href="https://www.reddit.com/r/piano/comments/grk09i/liszts_silent_piano_for_practicing_when_he_was_on/" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
How Fast did Beethoven and Chopin really Play? Check out <a href="https://www.youtube.com/watch?v=6EgMPh_l1BI" target="_blank">this video</a> to find out!
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. FALSE, 2. TRUE, 3. FALSE, 4. TRUE</div>

<p>Here at pianoLIT we are working non-stop to improve and expand our music library. If you wish to see the recording of any piece, please reply to this email with your request and we’ll jump on it. We’ve already received some great input (you’ll see them soon in the app!) and thanks our users for having such great ideas.</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Download PianoLIT here
@endcomponent

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
