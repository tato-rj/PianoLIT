@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'Life is a lot like jazz… It’s better when you improvise',
	'author' => 'G. Gershwin'
])

We are not really sure where the time went but we are officially halfway through 2020. And let’s admit this half-year has been all but what we would have expected.

@component('mail::panel')
# How do you think we're doing?
If you’ve downloaded the PianoLIT app, please tell us what you think <a href="https://forms.gle/BLG89NvjmkWdwSVMA" target="_blank">here</a>! This helps us improve and provide you with the best tools for your musical journey.
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'listen', 
		'color' => 'green',
		'link' => 'https://www.youtube.com/watch?v=g49mQTGI4jM'
	],
	'title' => 'The ballad of Blind Tom'
])

<p>Georgia 1849. Charity, a slave woman, gives birth to anywhere between her twelfth and twenty-first child. A blind boy is born, named Thomas. This is how the story of Thomas Wiggins and his incredible musical gift starts. Tom went to live until he was 61, dazzling audiences and composing wonderful music.</p>

<p>A great example is his “Sewing Song”, a piano lyrical piece inspired by the motions of a sewing machine. The writing is clean and very pianist, making this a great piece for late intermediate pianists.</p>

<p><a href="https://www.youtube.com/watch?v=g49mQTGI4jM" class="text-md"><strong>Sewing Song by Thomas Wiggins ("Blind" Tom), watch on YouTube</strong></a></p>
<p><a href="https://pianolit.com/pieces/816" class="text-md"><strong>You'll find the score here</strong></a></p>
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'orange',
		'link' => 'https://pianolit.com/blog/i-will-close-now-it-is-growing-dark'
	],
	'title' => 'I will close now. It is growing dark...'
])

<p>In February 1854, Robert Schumann attempted suicide by jumping off a bridge over the Rhine river. The following is an account of this tragic event and the final years of one of the greatest composers of all time.</p>

@component('mail::button', ['url' => 'https://pianolit.com/blog/i-will-close-now-it-is-growing-dark'])
Read more about this
@endcomponent


{{-- <p><a href="https://pianolit.com/resources/infographs/johannes-brahms" class="text-md" target="_blank">Brahms infographic, click HERE</a></p> --}}
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'quiz', 
		'color' => 'yellow',
		'link' => 'https://pianolit.com/quizzes/lets-put-your-classical-music-world-history-knowledge-to-the-test'
	],
	'title' => 'QUIZ: Let\'s put your classical music & world history knowledge to the test'
])

<p>You're a music expert... What about world history? Test your knowledge of world history events that were happening during key moments in classical music.</p>

@component('mail::button', ['url' => 'https://pianolit.com/quizzes/lets-put-your-classical-music-world-history-knowledge-to-the-test'])
Let me try!
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'infographic', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/resources/infographs/instruments-range'
	],
	'title' => 'Instruments range'
])

<p>This infographic was dictated from a personal need to find a more visually appealing representation of the range of the main orchestral instruments related to the 88 keys on the modern piano. Hope you like it, download it for FREE!</p>

@component('mail::button', ['url' => 'https://pianolit.com/resources/infographs/instruments-range'])
Download the infographic for FREE!
@endcomponent
@endcomponent

<h1 class="text-lg mb-4">True or False: American composers</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>George Gershwin only wrote Jazz style music:</strong> TRUE | FALSE',
	'<strong>Thomas Wiggins, also known as Blind Tom, played for the famous writer Mark Twain:</strong> TRUE | FALSE',
	'<strong>Aaron Copland is an Oscar-winning writer of film scores as well:</strong> TRUE | FALSE',
	'<strong>Edward MacDowell is a pioneer of the modern atonal music:</strong> TRUE | FALSE'
]])

<div class="text-center mt-4">Find the answers at the bottom of the newsletter!</div>

@include('mail::divider', ['orientation' => 'vertical'])

<h1 class="text-lg mb-4">Cool facts worth sharing this month:</h1>

@component('mail::panel')
Ever wondered how Liszt may have looked like in real life? Sure there are paintings, daguerreotypes and even some late photos portraying him but the CG artist Had Karimi has really given us a window to the past with his reconstruction of the great composer.

Check it out <a href="https://cgsociety.org/c/featured/4x8y/franz-liszt" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Watch <a href="https://www.youtube.com/watch?v=K0Vn9V-tRCo" target="_blank">THIS</a> excellent video explaining a problem in modern-day western music called by the narrator as “the death of Melody”. The concept is well laid out and demonstrated using examples from pop to movie to classical music.
@endcomponent

@component('mail::panel')
Learn how famed director Leopold Stokowski rose to celebrity in the US and how he changed American taste for orchestra. This beautifully written article offers a glimpse through the artist's life, from his beginning in Cincinnati to his late years in England.

Learn more about it <a href="http://www.listenmusicculture.com/recommended/matinee-idol-stokowski" target="_blank">here</a>.
@endcomponent

@component('mail::panel')
The coronavirus has led to a historic reshuffling around the country as a lot of people have moved due to the pandemic. If you own a piano, moving is always an added stress! Read <a href="https://www.cnn.com/2020/02/11/americas/piano-fazioli-smashed-movers-intl-scli/index.html">HERE</a> how movers accidentally dropped Angela Hewitt Fazioli’s handmade piano.
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. FALSE, 2. TRUE, 3. TRUE, 4. FALSE</div>

<p>Here at PianoLIT we are working non-stop to improve and expand our music library. If you wish to see the recording of any piece, please reply to this email with your request and we’ll jump on it. We’ve already received some great input (you’ll see them soon in the app!) and thanks to our users for having such great ideas.</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Download PianoLIT here
@endcomponent

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
