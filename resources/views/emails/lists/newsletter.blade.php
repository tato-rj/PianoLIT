@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'The notes I handle no better than many pianists. But the pauses between the notes - ah, that is where the art resides!',
	'author' => 'Artur Schnabel'
])

We’re living through a once-in-a-generation event, and like you, we’ve been finding it difficult to make sense of it all. The coronavirus has obliterated many things about normal life, we are spending a whole lot of time at home and have no excuses about not practicing!

It's clear we are going to be living like this for a while, so we might as well get the best out of it.

@component('mail::panel')
# How do you think we're doing?
If you’ve downloaded the PianoLIT app, please tell us what you think <a href="https://forms.gle/BLG89NvjmkWdwSVMA" target="_blank">here</a>! This helps us improve and provide you with the best tools for your musical journey.
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'listen', 
		'color' => 'green',
		'link' => 'https://www.youtube.com/watch?v=HbCoekpDCKc'
	],
	'title' => 'A Magical Music Box ✨'
])

<p>We've recently added this wonderful piece into our app’s “Playlists” tab, under the Hidden Gems category. We literally fell in love with it. It’s probably because of the nostalgia it evokes. It sounds like a music box playing an old charming tune.</p>

<p>The composer Anatoly Lyadov was a Russian musician whose orchestral works and poetic polished piano miniatures earned him a position of stature in Russian Romantic music.</p>

<p><a href="https://www.youtube.com/watch?v=HbCoekpDCKc" class="text-md"><strong>The Music Box Op.32, watch on YouTube</strong></a></p>
<p><a href="https://pianolit.com/pieces/778" class="text-md"><strong>You'll find the score here</strong></a></p>
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'orange',
		'link' => 'https://pianolit.com/blog/tchaikovsky-vs-brahms-a-story-of-music-and-rivalry'
	],
	'title' => 'Brahms vs Tchaikovsky: a story of music and rivalry'
])

<p>Both composers, ironically born on the same day May 7th, never missed a chance to express their disdain for each other's music. Let's have a look at some of what they had to say!</p>

@component('mail::button', ['url' => 'https://pianolit.com/blog/tchaikovsky-vs-brahms-a-story-of-music-and-rivalry'])
Read more about this
@endcomponent

<p>To learn more about their life and work download the PianoLIT infographic! Free to download here</p>

<p><a href="https://pianolit.com/resources/infographs/johannes-brahms" class="text-md" target="_blank">Brahms infographic, click HERE</a></p>
<p><a href="https://pianolit.com/resources/infographs/pytor-tchaikovsky" class="text-md" target="_blank">Tchaikovsky infographic, click HERE</a></p>
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'quiz', 
		'color' => 'yellow',
		'link' => 'https://pianolit.com/quizzes/would-you-recognize-your-favorite-composers-when-they-were-kids'
	],
	'title' => 'QUIZ: Would you recognize your favorite composers when they were kids?'
])

<p>We know classical composers by their famous portraits, usually taken in the height of their careers. But how did they look like as kids? We did some research and collected some.</p>

<p>Let's see how many you can identify!</p>

@component('mail::button', ['url' => 'https://pianolit.com/quizzes/would-you-recognize-your-favorite-composers-when-they-were-kids'])
Let me try!
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'crash course', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/crashcourses/practical-tips-to-play-chopins-revolutionary-etude-better'
	],
	'title' => 'Practical tips to practice Chopin\'s Revolutionary Etude'
])

<p>This FREE crash course is dedicated to those with a solid foundation and looking to improve Chopin's Revolutionary Etude.</p>

@component('mail::button', ['url' => 'https://pianolit.com/crashcourses/practical-tips-to-play-chopins-revolutionary-etude-better'])
Sign up for FREE!
@endcomponent
@endcomponent

<h1 class="text-lg mb-4">True or False: Tchaikovsky & Brahms edition</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>Tchaikovsky believed that music should primarily express our emotions:</strong> TRUE | FALSE',
	'<strong>Brahms was a staunch conservative and classicist:</strong> TRUE | FALSE',
	'<strong>Both Tchaikovsky and Brahms famously composed music for ballet:</strong> TRUE | FALSE',
	'<strong>Brahms and Tchaikovsky never had the opportunity to actually meet in person:</strong> TRUE | FALSE'
]])

<div class="text-center mt-4">Find the answers at the bottom of the newsletter!</div>

@include('mail::divider', ['orientation' => 'vertical'])

<h1 class="text-lg mb-4">Cool facts worth sharing this month:</h1>

@component('mail::panel')
Ever wondered how Tchaikovsky voice might have sounded like? Well, there’s a recording for that!

The following recording was made in Moscow in January 1890, by Julius Block on behalf of Thomas Edison. According to musicologist Leonid Sabaneyev, Tchaikovsky was not comfortable with being recorded for posterity and tried to shy away from it. Here below a transcription of what you’ll be hearing in this recording.

Listen to the recording <a href="https://www.youtube.com/watch?v=7DEEdFLjUiw&feature=youtu.be" target="_blank">here</a>!

Here's a transcription of the conversation:

- Anton Rubinstein: What a wonderful thing.
- J. Block: Certainly.
- E. Lavrovskaya A disgusting...how he dares slyly to name me.
- Vasily Safonov: (Sings).
- P. Tchaikovsky: This trill could be better.
- E. Lavrovskaya: (sings).
- P. Tchaikovsky: Block is a good fellow, but Edison is even better.
- E. Lavrovskaya: (sings) A-o, a-o.
- V. Safonov: (In German) Peter Jurgenson in Moscow.
- P. Tchaikovsky: Who just spoke? It seems to have been Safonov. (Whistles)
@endcomponent

@component('mail::panel')
There are many great photos of Brahms, one of which is with his friend, the composer Johann Strauss. 

<a href="https://www.gettyimages.com/detail/news-photo/german-composers-johann-strauss-and-johannes-brahms-circa-news-photo/164071334?adppopup=true" target="_blank">Check it out for yourself!</a>
@endcomponent

@component('mail::panel')
We’re reading a very interesting book called “The ballad of blind Tom”. In this book, the author describes the life if Blind Tom Wiggins, a blind African-American composer with an astonishing musical gift, born into slavery in the US of the late 19th century. 

We’ll be making a blog post of the book once finished, so stay tuned if you’re curios to know more about blind Tom and his incredible music! 

Find the book <a href="https://www.amazon.com/Ballad-Blind-Tom-Slave-Pianist-ebook/dp/B07RW44KW9/ref=sr_1_2?dchild=1&keywords=blind+tom&qid=1589513880&sr=8-2" target="_blank">here</a>.
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. TRUE, 2. TRUE, 3. FALSE, 4. FALSE</div>

<p>Here at pianoLIT we are working non-stop to improve and expand our music library. If you wish to see the recording of any piece, please reply to this email with your request and we’ll jump on it. We’ve already received some great input (you’ll see them soon in the app!) and thanks to our users for having such great ideas.</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Download PianoLIT here
@endcomponent

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
