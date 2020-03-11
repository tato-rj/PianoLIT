@component('mail::message', ['subscription' => $subscription, 'list' => $list])

@include('mail::newsletter.quote', [
	'quote' => 'Ceaseless work, analysis, reflection, writing much, endless self-correction, that is my secret.',
	'author' => 'Johann Sebastian Bach'
])

<p class="text-center m-0"><strong>We've rolled out an update to the PianoLIT app!</strong></p>
<p class="text-center">What comes with this update?</p>

- You can now see the tutorials you've requested in a completely new section under the "My pieces" tab. Any previously published requests or the new one, are now easily accessible and neatly organized.

- The "Explore" tab is arranged in subsections clarifying the scope of each tag and helping you find exactly what you're looking for.

As always, you can expect an original Free Pick every week and new pieces and tutorials added on a daily basis.

What's next? We're already working to add more cool features plus... anything that you think we should implement! We're here for you, so please feel free to drop us a note by replying to this newsletter!

@include('mail::divider', ['orientation' => 'vertical'])

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'listen', 
		'color' => 'green',
		'link' => 'https://www.youtube.com/watch?v=vipdZu74I-4'
	],
	'title' => 'Latin-American rhythm 💃'
])

<p>We all know the exotic Argentinian dance of tango which evolved from the lower-class districts of Buenos Aires to reach fame in fashionable European circles during the 1915. The early tangos were spirited and lively, but later the music and lyrics became intensely melancholy. the word "tango" was often applied to dances in a 2/4 or 4/4 rhythm such as the one-step.</p>

<p>This interesting piece by Timothy Brown takes the exhuberance and pop knowledge of th efamous dance and translates it into a great piece ofr mid intermediate pianists. One of the main challanges in this piece is to interpret correctly the piece's character by understanding what a tango should sound like. Marrying this idea with a lean and clear sound on left hand octaves.</p>

<p><a href="https://www.youtube.com/watch?v=vipdZu74I-4" class="text-md"><strong>"Tango Romantico in C minor, watch on YouTube</strong></a></p>
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'orange',
		'link' => 'https://pianolit.com/blog/how-much-money-did-chopin-make-with-his-compositions'
	],
	'title' => 'How much money did Chopin make with his compositions?'
])

<p> If composers like Chopin were alive today they would certainly be making a lot of money, but that was not always the case in their lifetime. Chopin's letters give us a sneak peek into his finances and how much he actually made. </p>

<p>🎁 <strong>We have a gift for you!</strong> While reading the blog post, you can download the FREE infographic of Chopin's life.</p>

<p class="text-md"><a href="https://pianolit.com/blog/how-much-money-did-chopin-make-with-his-compositions" target="_blank">Learn more about it!</a> 🤓</p>
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

<p>How many you can guess???</p>

<p class="text-md text-italic"><strong>DISCLAIMER:</strong> This quiz is kinda difficult, so far it has an average score of 5 out of 8! Can you beat that?</p>

@component('mail::button', ['url' => 'https://pianolit.com/quizzes/lets-put-your-classical-music-world-history-knowledge-to-the-test'])
Let me try!
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'infographics', 
		'color' => 'pink',
		'link' => 'https://pianolit.com/resources/infographs/1870s-a-musical-decade'
	],
	'title' => '1870s: A Musical Decade'
])

<p>The <strong>1870s</strong> was one of the most active decades in music history, with many major compositions and important events. <strong>Download the infographic</strong>  to visualize the list on a timeline graph. </p>

<p class="text-md"><a href="https://pianolit.com/resources/infographs/1870s-a-musical-decade" target="_blank">Download the infographic, click HERE</a></p>
@endcomponent

<h1 class="text-lg mb-4">True or False: Women composers edition</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>Fanny Mendelssohn was encouraged by his family to pursue composition, but she wasn\'t as talented as her brother Felix:</strong> TRUE | FALSE',
	'<strong>Clara Schumann was a child prodigy:</strong> TRUE | FALSE',
	'<strong>Florence Price was the first African-American woman to have her music performed by a major symphony orchestra:</strong> TRUE | FALSE',
	'<strong>Luise A. Le Beau studied with Clara Schumann only for a few months since the two did not get along so well:</strong> TRUE | FALSE'
]])

<div class="text-center mt-4">Find the answers at the bottom of the newsletter!</div>

@include('mail::divider', ['orientation' => 'vertical'])

<h1 class="text-lg mb-4">4 things worth sharing this month:</h1>

@component('mail::panel')
In Taiwan, <a href="https://www.youtube.com/watch?v=U2WboF86sKQ" target="_blank">The Maiden Prayer</a> is played on garbage trucks, along with <a href="https://www.youtube.com/watch?v=h7DPXpqp9e4" target="_blank">Beethoven's Fur Elise</a>, to alert citizen of the presence of a nearby truck! After discovering this, a trip to Taiwan is a must.
@endcomponent

@component('mail::panel')
At first glance, this may not look like something that can be found at one of the most famous opera houses in the world, however you might recognize it if you've ever seen Phantom of the Opera. The Paris Opera House lake resides underneath the <a href="https://artsandculture.google.com/streetview/MAFGZDrEiCo04g?sv_pid=CAISFnd2bzRIXzdKSzhZQUFBR3VzbTVqTVE%3D&sv_f=90.0&sv_h=257.20039567333276&sv_p=-10.678604083237943&hl=en" target="_blank">foundations of the grandiose building</a> due to the marshy area in which it was built. Check it out for yourself with this virtual tour. After touring the foundations, peak at the <a href="https://artsandculture.google.com/streetview/op%C3%A9ra-national-de-paris/MwFixmW5o_f5jw?sv_h=165.8677520751953&sv_p=-0.34783935546875&sv_pid=yhSFWB9ONtfp29xwyB2BTw&sv_lid=13356395696129231462&sv_lng=2.3316430726833914&sv_lat=48.87215089180122&sv_z=0.05254531846550925&hl=en" target="_blank">magnificent stage</a> and lastly see the beautiful <a href="https://artsandculture.google.com/streetview/roofs-of-the-palais-garnier/MwHej74z895Uvg?sv_lng=2.331656506485047&sv_lat=48.87223414775861&sv_h=200.0053574099009&sv_p=2.110519683306009&sv_pid=N9hs0LHI4AAAAAGuvISryg&sv_z=1&hl=en" target="_blank">view from the roof</a>!
@endcomponent

@component('mail::panel')
if you wish to know more about American composer Edward MacDowell, look no further than this great study by Lawrence Gilman available to read FOR FREE at The Project Gutenberg website. Start reading it <a href="http://www.gutenberg.org/files/14109/14109-h/14109-h.htm#imgIX" target="_blank">here</a>.
@endcomponent

@component('mail::panel')
Have you ever noticed how Darth Vader Imperial March is very similar to Chopin's Funeral March? We are convinced Darth Vader would've loved the reference (｡▼皿▼)
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. FALSE, 2. TRUE, 3. TRUE, 4. TRUE</div>

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
