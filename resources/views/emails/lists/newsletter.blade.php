@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'I often think in music. I live my daydreams in music. I see my life in terms of music.',
	'author' => 'A. Einstein'
])

<p>If September is your back-to-class or just back-to-reality month, we have some new great piano music that will make this transition from summer to early fall a bit easier. Get the PianoLIT app and start exploring.</p>

<p>We are very excited to announce two new resources we are releasing this month!</p>

<p>Our new <strong>eBooks</strong> will cover a wide variety of piano related topics, ranging from theory, sight reading, history and more. We are also developing <strong>eScores</strong>, which are our own editions of impossible-to-find piano scores. These are true gems of the piano repertoire for every mood and level.</p>

<p class="text-center"><strong>STAY TUNED🤗!</strong></p>

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
	'title' => 'When a dream is translated for piano'
])

<p>Many composers throughout music history have tried to capture this feeling of dreaming in their music. Whether is a day-dreaming kind of state, also called rêverie, or a night-dreaming the point is to create melodies that capture perfectly this lost-in-thought, meditative mood.</p>

<p><a href="https://www.youtube.com/watch?v=-mNdUXpYLsk" class="text-md"><strong>Watch Dreaming Opus 15 No.3 by Amy Beach</strong></a></p>

<p><a href="https://my.pianolit.com/storage/app/score/pianolit-dreaming-in-gb-major-from-4-sketches-op15-no3-8596.pdf" class="text-md"><strong>Download the FREE score here</strong></a></p>

<p>Based on a poem by French poet Victor Hugo, this is a remarkable piece in every possible way. The writing is intricate and expressive, the musical ideas are original and encompass a wide range of emotions.</p>

<p>The main challenge here is to play the accompaniment parts. The rhythm and direction of the notes are intentionally inconsistent, and the eighth notes move freely around the harmony as if it was floating in the air, like in a dream.</p>
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'orange',
		'link' => 'https://pianolit.com/blog/the-romantic-period-in-a-nutshell'
	],
	'title' => 'The Romantic period in a nutshell'
])

<p>A quick glance at the Romantic period in music, with a special focus on the role of piano and the compositions that characterized this time in history.</p>

@component('mail::button', ['url' => 'https://pianolit.com/blog/the-romantic-period-in-a-nutshell'])
Read more about this
@endcomponent

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'quiz', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/quizzes/how-many-correct-answers-can-you-get-the-basics-you-should-know-about-scriabin'
	],
	'title' => 'QUIZ: How many correct answers can you get? The basics you should know about Scriabin'
])

<p>Test your knowledge about the great Russian composer Alexander Scriabin and his fascinating music!</p>

@component('mail::button', ['url' => 'https://pianolit.com/quizzes/how-many-correct-answers-can-you-get-the-basics-you-should-know-about-scriabin'])
Let me try!
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'infographic', 
		'color' => 'yellow',
		'link' => 'https://pianolit.com/resources/infographs/women-composers'
	],
	'title' => 'Women Composers of Classical Music'
])

<p>This infographic highlights 6 remarkable women composers of classical music, from the early 19th century until today.</p>

@component('mail::button', ['url' => 'https://pianolit.com/resources/infographs/women-composers'])
Download the infographic for FREE!
@endcomponent
@endcomponent

<h1 class="text-lg mb-4">True or False: Dreaming in music</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong> Debussy composed not one but three pieces called <i>rêverie</i>:</strong> TRUE | FALSE',
	'<strong>Liszt’s Liebestraum translates to Dreams of Love:</strong> TRUE | FALSE',
	'<strong>The famous Schumann’s piece Traumerei (Dreaming) is part of the composer’s collection called "Scenes from Childhood”</strong> TRUE | FALSE',
	'<strong>Amy Beach piece “Dreaming”, opus 15 no.3 is based on a poem by Victor Hugo:</strong> TRUE | FALSE'
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
Are you prone to road rage at times? The German government is worried about the high number of road accidents on the country’s motorways (2.4 million annually). Many of these accidents are caused by aggressive driving and road rage. 

To counteract this, the German Ministry of Transport has released a CD for drivers which features Mozart’s Piano Concerto No.21. played by the Minister himself! He hopes that the soothing effects of music will calm drivers down. 

(Fun fact: There is no word in German for road rage).

Read more <a href="https://www.wqxr.org/story/168660-german-minister-combats-road-rage-mozart/" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Ever wondered how Clara Schumann would look like in person? Well, CGI artist Had Karimi is providing us with a window to the past! Check out Clara’s bust reconstruction here.

Check it out <a href="https://cgsociety.org/c/featured/fcup/clara-schumann-1856" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Fans have flocked to a church in Germany to hear a chord change in a musical composition that lasts for 639 years. Yes, it’s not a typo. The work is by the avant-garde American composer, John Cage called As Slow As Possible. It began 19 years ago and changed chord for first time in seven years. The score is made up of eight pages of music, to be played at the piano or organ - very slowly. So slowly that the piece will end in 2640.

Read more about this <a href="https://www.bbc.com/news/world-europe-54041568" target="_blank">HERE</a>.
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. FALSE, 2. TRUE, 3. TRUE, 4. TRUE</div>

<p>Here at pianoLIT we are working non-stop to improve and expand our music library. If you wish to see the recording of any piece, please reply to this email with your request and we’ll jump on it. We’ve already received some great input (you’ll see them soon in the app!) and thanks our users for having such great ideas.</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Download PianoLIT for iOS here
@endcomponent

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
