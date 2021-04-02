@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'It appeared that nobody ever said a thing they meant, or ever talked of a feeling they felt, but that was what music was for.',
	'author' => 'Virginia Woolf'
])

<p>This past month of March was Women’s History Month. We encourage you to listen to (and play) the music of incredible artists such as Price, Beau, Chaminade, Bonis, Rahbee, Ignesi, Farrenc, Bentley to name only a few. Visit PianoLIT discover page to learn more about music written by women composers.</p>

<p>🎁 Are you a member? <a href="https://pianolit.com/ebooks/music-intervals" target="_blank">Our latest eBook</a> explaining Music Intervals is <u>included with a membership plan</u>. Sign up now and get it for <strong>FREE</strong>!</p>

@component('mail::panel')
# How do you think we're doing?
If you’ve downloaded the PianoLIT app, please tell us what you think <a href="https://forms.gle/BLG89NvjmkWdwSVMA" target="_blank">HERE</a>! This helps us improve and provide you with the best tools for your musical journey.
@endcomponent

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'listen', 
		'color' => 'green',
		'link' => 'https://www.youtube.com/watch?v=PDoCKfp17CM'
	],
	'title' => 'Happily ever after'
])

<p>This piece is from a Suite of 6 pieces that tell the famous story of Sleeping Beauty. These are programmatic pieces that depict incredibly well the mood and character of each scene. They are also very pianistic and a great choice to supplement the repertoire of intermediate level pianists.</p>

<p>It was written by Berenice Benson Bentley, an extremely talented composer, who wrote  beautiful melodies and captivating music, but very little is known about her life. What we do know is that the name, Berenice Benson Bentley, was a pen name.</p>

<p><a href="https://www.youtube.com/watch?v=PDoCKfp17CM" class="text-md"><strong>Watch “Happy Ever After” by B. B. Bentley</strong></a></p>

<p><a href="https://pianolit.com/storage/app/score/pianolit-happy-ever-after-in-d-major-from-the-sleeping-beauty-9722.pdf" class="text-md"><strong>Get the score for FREE here</strong></a></p>

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'orange',
		'link' => 'https://pianolit.com/blog/the-untold-secret-of-the-moonlight-sonata'
	],
	'title' => 'The untold secret of the Moonlight Sonata'
])

<p>Beethoven's Moonlight Sonata is undoubtedly one of the most famous classical piano pieces of all time, and yet it may well be among the most misunderstood.</p>

@component('mail::button', ['url' => 'https://pianolit.com/blog/the-untold-secret-of-the-moonlight-sonata'])
Learn more about the Moonlight here
@endcomponent

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'eScore', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/escores/sewing-song'
	],
	'title' => 'Sewing Song'
])

<p>Get the redacted score of this gorgeous piece by Thomas Wiggins, a great African American composer that should be heard and played more.</p>
<p>The Sewing Song is an extraordinary piece that combines heartfelt and tender moments with fiery and passionate sections. A perfect example of Wiggins’ extraordinary ability to transform everyday sounds into poetic piano music.</p>
<p>PianoLIT editions have an intro with the composer’s bio and the link for the video performance of the piece. And for subscribers they are free to download. So hurry up, don’t miss this chance!</p>

@component('mail::button', ['url' => 'https://pianolit.com/escores/sewing-song'])
Get the eScore here
@endcomponent

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'crash course', 
		'color' => 'yellow',
		'link' => 'https://pianolit.com/crashcourses/improve-your-scales-in-5-days'
	],
	'title' => 'Improve your scales in 5 days'
])

<p>Take a 5 days immersion in the scales piano technique. Learn more about it, improve it by using effective practicing tips and test your acquired knowledge with a piece focused on master it.</p>

@component('mail::button', ['url' => 'https://pianolit.com/crashcourses/improve-your-scales-in-5-days'])
Start the FREE crash course today!
@endcomponent
@endcomponent

<h1 class="text-lg mb-4">True or False: Impressionist music</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>To play scales you need to develop a good dexterity:</strong> TRUE | FALSE',
	'<strong>Legato in music means to play with short and quik sounds:</strong> TRUE | FALSE',
	'<strong>The very first scales Chopin used to teach to his beginner students are the ones with all black keys in them (F# and C# major):</strong> TRUE | FALSE',
	'<strong>Up until the Baroque period, keyboardists only occasionally used their thumbs when playing scales:</strong> TRUE | FALSE'
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
The Hurrian Hymn is History's first Song. Actually the earliest written song that can be reconstructed. We're not even sure what the song even sounds like, but each attempt to decode has its own value. All we are sure about is that the Hurrian Hymn is really, really ancient and nothing is as clear as it seems. Watch this video to learn more.

Listen to it <a href="https://www.youtube.com/watch?v=KElPnD-dbkk" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
By using an interactive webpage, sound artist Yuri Suzuki has written 10 rules of creativity to live by.

Check this out <a href="https://wepresent.wetransfer.com/story/manifesto-yuri-suzuki-pentagram/" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Ever wondered how a computer animated music would oust like? Ask no more… Check out this video called “Resonant chamber” by the company Animusic.

Watch it <a href="https://www.youtube.com/watch?v=toXNVbvFXyk" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Check out an old music typewriter. It was 1936 when Robert H. Keaton, a resident of San Francisco, California and a self-proclaimed inventor, patented the typewriter which instead of letters, printed musical notes.

Learn more <a href="https://www.thevintagenews.com/2019/03/21/musical-typewriter/" target="_blank">HERE</a>.
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. TRUE, 2. FALSE, 3. TRUE, 4. TRUE</div>

<p>Here at PianoLIT we are working non-stop to improve and expand our music library. If you wish to see the recording of any piece, please reply to this email with your request and we’ll jump on it. We’ve already received some great input (you’ll see them soon in the app!) and thanks to our users for having such great ideas.</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Download PianoLIT for iOS here
@endcomponent

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
