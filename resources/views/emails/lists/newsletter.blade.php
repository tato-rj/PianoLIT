@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'There are still so many beautiful things to be said in C Major',
	'author' => 'S. Prokofiev'
])

<p>November has only started and is already being marked by coronavirus resurgence, political unrest around the world, and wonderful new music additions on the PianoLIT platform. Make sure to check out our newest pieces and don’t forget to update your app if you use iOS devices.</p>

<p>Head over to the <a href="https://my.pianolit.com/playlists" target="_blank">PianoLIT App</a> and start exploring our featured playlist “Scary vibes”.</p>

@component('mail::panel')
# How do you think we're doing?
If you’ve downloaded the PianoLIT app, please tell us what you think <a href="https://forms.gle/BLG89NvjmkWdwSVMA" target="_blank">here</a>! This helps us improve and provide you with the best tools for your musical journey.
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'listen', 
		'color' => 'orange',
		'link' => 'https://www.youtube.com/watch?v=FSk7NulJWoA'
	],
	'title' => 'Birds'
])

<p>We’ve been directed to Bernstein’s own compositions by a PianoLIT user. After digesting his collections inspired by birds, we were quite frankly were amazed by this artist’s creativity. The first collection is a fantastic suite of 8 studies: the purple finch, the hummingbird, the woodpecker, the seagull, the chickadee, the vulture, the penguin, the eagle.</p>
<p>The second volume follows the first with 9 more studies: myna bird, the swan, the robin, the owl, the roadrunner, the condor, the nightingale, the guinea hen, the phoenix. Some of them, like the swan, contain homages to previous composers like Saint-Saens (from Saint-Saens’ own Swan in the Carnival of the Animals).</p>

<p><a href="https://www.youtube.com/watch?v=FSk7NulJWoA" class="text-md"><strong>Watch “The Roadrunner” by S. Bernstein</strong></a></p>

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'eScore', 
		'color' => 'green',
		'link' => 'https://pianolit.com/escores/sewing-song-by-t-wiggins-blind-tom'
	],
	'title' => 'Sewing song by Thomas Wiggins'
])

<p>Born into slavery in Georgia, Thomas Wiggins' life was one of the most incredible and moving episodes in American history. Blind and autistic, and so unable to work with other slaves, Thomas found inspiration in the sounds of the natural world and music. The Sewing Song is an extraordinary piece that combines heartfelt and tender moments with fiery and passionate sections. The writing is clean and very pianistic, making this a great piece for late intermediate pianists.</p>

@component('mail::button', ['url' => 'https://pianolit.com/escores/sewing-song-by-t-wiggins-blind-tom'])
Get eScore here
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/blog/music-intervals-in-a-nutshell'
	],
	'title' => 'Music Intervals in a Nutshell'
])

<p>The distance between two notes in music is just like the distances between objects in the "real world". Being familiar with this measuring system is fundamental to understanding how music works behind the curtains.</p>

@component('mail::button', ['url' => 'https://pianolit.com/blog/music-intervals-in-a-nutshell'])
Check out this post
@endcomponent

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'infographic', 
		'color' => 'yellow',
		'link' => 'https://pianolit.com/resources/infographs/jsbachs-ornaments-table'
	],
	'title' => 'J.S.Bach\'s Ornaments Table'
])

<p>J.S.Bach wrote this table in the "Klavierbüchlein für Wilhelm Friedemann Bach", a book he created for the keyboard education of his eldest son Wilhelm. This table is used to this day as a standard for baroque ornaments.</p>

@component('mail::button', ['url' => 'https://pianolit.com/resources/infographs/jsbachs-ornaments-table'])
Download the infographic for FREE!
@endcomponent
@endcomponent

<h1 class="text-lg mb-4">True or False: All about Seymour Bernstein</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>He struggled with performance anxiety and quit the stage in 1977:</strong> TRUE | FALSE',
	'<strong>He is featured in a documentary directed by Hollywood actor Ethan Hawke:</strong> TRUE | FALSE',
	'<strong>He was born and raised in Newark, New Jersey:</strong> TRUE | FALSE',
	'<strong>He is not into teaching and avoids playing or composing pedagogical music:</strong> TRUE | FALSE'
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
Interesting article on “Why you should still listen to classical music”. To share with your friends that avoid classical music at all costs.

Read more <a href="https://medium.com/@manuel_brenner/why-you-should-still-be-listening-to-classical-music-e0429aef69a3" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Did you know about Sophie Menter, virtuoso pianist and composer (1846-1918)? She was known for her fiery temperament and brilliance as a performer. In Paris, her nickname was “l’incarnation de Liszt”. She also was Liszt’s student.

Listen to her Mazurka, Op.6 <a href="https://www.youtube.com/watch?v=gUt8WUtC8EQ" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
The Netflix “ta-dum” sound mark is one of all-time greats but doesn’t work as well in the theater because is only 3 seconds long. So Netflix commissioned Hans Zimmer to extend it for theaters. And it’s just…. so…. good!!

Watch it <a href="https://www.youtube.com/watch?v=phG4_0MpT4M" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
We all know how a great musician Mendelssohn is, but did you know he was also quite an incredible drafter and painter?

Learn more about it <a href="https://www.loc.gov/item/ihas.200156435/" target="_blank">HERE</a>.
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. TRUE, 2. TRUE, 3. TRUE, 4. FALSE</div>

<p>Here at PianoLIT we are working non-stop to improve and expand our music library. If you wish to see the recording of any piece, please reply to this email with your request and we’ll jump on it. We’ve already received some great input (you’ll see them soon in the app!) and thanks to our users for having such great ideas.</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Download PianoLIT for iOS here
@endcomponent

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
