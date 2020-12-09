@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'I have never written a note I didn’t mean',
	'author' => 'E. Satie'
])

<p>We would like to use this month’s introduction to give a shoutout to <strong>A Seat at the Piano</strong> and the incredible work they are doing with their project.</p>

<p>ASAP was founded in the summer of 2020 in the midst of social and racial reckoning around the world. Their mission is to promote and advocate for the inclusion, study, and performance of a more equitably representative body of piano works. With their website platform, they strive to raise the voices of those who are less heard and to inspire more thoughtful, inclusive programming within the performing and pedagogical spheres.</p>

<p>Check their website <a href="https://www.aseatatthepiano.com/" target="_blank">here</a> 👏👏👏</p>

@component('mail::panel')
# How do you think we're doing?
If you’ve downloaded the PianoLIT app, please tell us what you think <a href="https://forms.gle/BLG89NvjmkWdwSVMA" target="_blank">here</a>! This helps us improve and provide you with the best tools for your musical journey.
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'webapp', 
		'color' => 'orange',
		'link' => 'https://my.pianolit.com/pieces/859'
	],
	'title' => 'Silent Night'
])

<p>Max Reger was a German composer and teacher, active during the last years of the ‘800 and the first decades of the ‘900 and famous for his organ works. His opus 17 is a collection of 20 short pieces that brings back fond memories from the composer's youth, much like Schumann's Opus 15 "Scenes from Childhood". These are not easy pieces for beginners but rather inspired by the composer's memories.</p>

<p>One piece in particular, is a rendition of the famous and beloved Silent Night. Here the right hand resembles the delicate sound of tiny bells accompanying the main theme. The coordination between the distinct rhythmic groups in the right hand and the melody in the left hand is the main challenge.</p>

<p><a href="https://youtu.be/-4uwtdIKtmQ" class="text-md"><strong>Watch “Silent Night” by M. Reger</strong></a></p>

<p><a href="https://pianolit.com/storage/app/score/pianolit-silent-night-in-a-major-from-from-childhood-op17-no9-4560.pdf" class="text-md"><strong>Get the score for FREE here

</strong></a></p>

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/blog/classical-piano-music-like-clair-de-lune'
	],
	'title' => 'Classical piano music like Clair de Lune'
])

<p>Always loved Debussy's masterwork Clair de Lune and want to play something similar? You are in the right place. Keep on reading to discover new classical piano music like Clair de Lune.</p>

@component('mail::button', ['url' => 'https://pianolit.com/blog/classical-piano-music-like-clair-de-lune'])
Check out this post
@endcomponent

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'eScore', 
		'color' => 'green',
		'link' => 'https://pianolit.com/escores/sonata-in-f'
	],
	'title' => 'Sonata in F by M.T. Agnesi'
])

<p>The early decades of the Classical period were marked by vibrant, bold, and uplifting music. The writing was simple and the texture clean, providing incredible contrast to the music of the Baroque period that had just ended.</p>

<p>This beautiful short sonata embodies the spirit of this generation and it will help you develop clean and even playing on the left hand, as well as nuanced scale passages on the right hand.</p>

@component('mail::button', ['url' => 'https://pianolit.com/escores/sonata-in-f'])
Get eScore here
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'resource', 
		'color' => 'yellow',
		'link' => 'https://pianolit.com/composers/birthdays'
	],
	'title' => 'Don\'t miss out on the birthday of any of your favorite composers'
])

<p>Check out this free resource listing every composers’ birthday in a neat calendar. So that you never forget about their special day ever again!</p>

@component('mail::button', ['url' => 'https://pianolit.com/composers/birthdays'])
See who has birthday this month
@endcomponent
@endcomponent

<h1 class="text-lg mb-4">True or False: Christmas music</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>Silent Night is a popular Austrian Christmas carol composed in 1818:</strong> TRUE | FALSE',
	'<strong>During his career J.S. Bach always refused to compose any music that was Christmas related:</strong> TRUE | FALSE',
	'<strong>Scriabin was born on Christmas Day:</strong> TRUE | FALSE',
	'<strong>Liszt wrote a suite of 12 pieces called “The Christmas Tree” suite:</strong> TRUE | FALSE'
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
Ever wondered how Beethoven really looked like if you had the privilege to meet him in real life? Well, GC artist Hadi Karimi gives us a door into the past with his remarkable computer reconstruction of the artist’s bust.

Read more <a href="https://hadikarimi.com/portfolio/ludwig-van-beethoven-1815" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Watch this opera singing cockatiel! 🦜

Check this out <a href="https://www.youtube.com/watch?v=QmpH-IgWW6Q&feature=emb_title" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Did you know the famed Steinway manufacturing plant is now making a piano designed in collaboration with Lenny Kravitz? We are not entirely sure how to feel about its look… but it would be more interesting to hear how it sounds. What do you think?

Read more about it <a href="https://www.steinway.com/pianos/steinway/limited-edition/kravitz-grand/?utm_source=vr&utm_medium=email&utm_campaign=201112" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
To end with a good laugh! 😂

Check it out <a href="https://www.instagram.com/p/CIOXUiBgjwj/" target="_blank">HERE</a>.
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. TRUE, 2. FALSE, 3. TRUE, 4. TRUE</div>

<p>Here at PianoLIT we are working non-stop to improve and expand our music library. If you wish to see the recording of any piece, please reply to this email with your request and we’ll jump on it. We’ve already received some great input (you’ll see them soon in the app!) and thanks to our users for having such great ideas.</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Download PianoLIT for iOS here
@endcomponent

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
