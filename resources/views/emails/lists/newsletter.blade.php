@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'When legendary cellist Pablo Casals was asked why he continued to practice at age 90, he responded: “Because I think I am making progress”',
	'author' => 'P. Casals'
])

<p>With October we have officially surpassed 3/4 of year 2020. And you know what this means: we are approaching the holidays and spending a whole lot more time indoor. So for your practicing time, we have some perfect pieces to get into the Halloween spirit.</p>

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
		'link' => 'https://www.youtube.com/watch?v=RPFURmmnEHs'
	],
	'title' => 'A haunted melody'
])

<p>When thinking about a piece to feature during the Halloween month, nothing better than Alkan’s “Song of the Madwoman by the Seashore”. A brilliant piece with a dark and sinister atmosphere. So evocative that it will get you chills down your spine while playing! While most of Alkan’s compositions are advanced music, this piece which is part of his Preludes Op. 31 is more around the late intermediate level.</p>

<p><a href="https://youtu.be/mC_yUTPg_Kg" class="text-md"><strong>Watch “Song of the Madwoman by the Seashore” by C. V. Alkan</strong></a></p>

<p><a href="https://my.pianolit.com/storage/app/score/pianolit-prelude-in-ab-minor-from-25-preludes-op31-no8-song-of-the-madwoman-on-the-seashore-1850.pdf" class="text-md"><strong>Download the FREE score here</strong></a></p>

<p>Charles Valentine Alkan was a pianist active during the Parisian years that saw Chopin and Liszt a the heart of the musical scene. Alkan was there among them. Extremely talented and a virtuoso, his music is very demanding technically. When he turned 35, Alkan began to adopt a reclusive life style, while continuing with his compositions.</p>
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'free ebook', 
		'color' => 'green',
		'link' => 'https://pianolit.com/ebooks/become-a-better-sight-reader-in-5-days'
	],
	'title' => 'Become a better sight-reader in 5 days - Start reading like a pro with practical tips and music scores for each day'
])

<p>If you've played piano before, you’re probably familiar with the concept of sight-reading. Sight-reading is the practice of reading and performing a piece that you've never seen before. The benefits of skillful sight-reading are many, but don’t fall in the mistake of thinking that sight-readers are born that way. The art of sight-reading can, and should, be taught.</p>

@component('mail::button', ['url' => 'https://pianolit.com/ebooks/become-a-better-sight-reader-in-5-days'])
Download the FREE eBook now
@endcomponent

@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/blog/core-piano-repertoire-that-every-pianist-should-know'
	],
	'title' => 'Core piano repertoire that every pianist should know'
])

<p>The piano repertoire is vast, but there are some pieces that every pianist, regardless of the level, should absolutely know. Here's the list.</p>

@component('mail::button', ['url' => 'https://pianolit.com/blog/core-piano-repertoire-that-every-pianist-should-know'])
Read the blog post
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'infographic', 
		'color' => 'yellow',
		'link' => 'https://pianolit.com/resources/infographs/speed-markings'
	],
	'title' => 'Speed markings'
])

<p>The most commonly used speed markings, along with a quick description for each of them.</p>

@component('mail::button', ['url' => 'https://pianolit.com/resources/infographs/speed-markings'])
Download the infographic for FREE!
@endcomponent
@endcomponent

<h1 class="text-lg mb-4">True or False: Charles Valentin Alkan’s music</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>He composed a funeral march for the death of a parrot:</strong> TRUE | FALSE',
	'<strong>He was a reclusive throughout his life and never met his famous colleagues Chopin and Liszt:</strong> TRUE | FALSE',
	'<strong>He was a child prodigy and later on a virtuoso:</strong> TRUE | FALSE',
	'<strong>He was a dedicated pedagogue and most of his repertoire is for beginner level:</strong> TRUE | FALSE'
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
On the occasion of Beethoven's 250th birthday, the Kunsthistorisches Museum in Vienna serves up a very special exhibition called “”Beethoven moves. It combines paintings, sketchbooks, graphics, sculptures, and videos with music and Beethoven the person. 

The exhibition is to be seen as a poetic reflection of his creative work. Beethoven's masterpieces enter into a connection with the masterpieces of the visual arts (painting, sculpture, graphics, drawings, photography, and video) and thus create a new total work of art of Beethoven. 

The visitor is invited to locate their own body in the space and thus to put it in relationship to the music, language, image, and movement. A whole-body experience that leads to a very special encounter with the great composer.

Read more <a href="https://www.wien.info/en/sightseeing/museums-exhibitions/beethoven-moves-khm" target="_blank">HERE</a>.

Watch the installation <a href="https://www.instagram.com/p/CF3yV48BBHz/?utm_source=ig_web_copy_link" target="_blank">HERE</a> (turn up your sound!).
@endcomponent

@component('mail::panel')
Remember when Wilma and Betty headed out for a night of Leonard Bernstone conducting Rockymaninoff at the Hollyrock Bowl? Watch it again! :)

Watch the video <a href="https://www.youtube.com/watch?v=Gy75VoLv04c" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
Have you ever heard of robots playing the cello? You’re in for a treat! Check out this Work In Progress: ”Cello Concerto No.1” - for industrial robots and string quintet (17′) (2019)

Watch it <a href="https://www.youtube.com/watch?v=8srZQ5iZDoY" target="_blank">HERE</a>.
@endcomponent

@component('mail::panel')
One of the most distinctive concert halls in the world! 🤩 The Palau in Barcelona. We cannot wait till concert halls like this are filled with music once again.

Watch the video <a href="https://www.youtube.com/watch?v=yr8TIFkppkA" target="_blank">HERE</a>.
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. TRUE, 2. FALSE, 3. TRUE, 4. FALSE</div>

<p>Here at pianoLIT we are working non-stop to improve and expand our music library. If you wish to see the recording of any piece, please reply to this email with your request and we’ll jump on it. We’ve already received some great input (you’ll see them soon in the app!) and thanks our users for having such great ideas.</p>

@component('mail::button', ['url' => config('app.stores.ios')])
Download PianoLIT for iOS here
@endcomponent

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
