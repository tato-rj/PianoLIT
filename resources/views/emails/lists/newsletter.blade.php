@component('mail::message', ['subscription' => $subscription, 'list' => $list])
@include('mail::newsletter.quote', [
	'quote' => 'I live in music like a fish in water.', 
	'author' => 'Camille Saint-Saëns'
])

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'listen', 
		'color' => 'green',
		'link' => '#'
	],
	'title' => 'Rising with music'])

<p>asdnajnsdkj sadkjnsjksa</p>
<p>jsadhsad iauduiash</p>

<p><a href="" class="text-md"><strong>Élévation from 6 Songs Without Words Op. 76 by C. Chaminade, watch on YouTube</strong></a></p>
@component('mail::button', ['url' => '#'])
ASDJNASKJn
@endcomponent
<p class="text-md text-italic"><strong>FUN FACT</strong>: In 1913, Chaminade was awarded the Légion d'Honneur, a first for a female composer.</p>
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'read', 
		'color' => 'green',
		'link' => '#'
	],
	'title' => 'FACT-CHECK: Truths and myths about Mozart'])

<p>With so many stories surrounding Mozart and his family, it is difficult to know exactly what is true and what has been "embellished" overtime... Here is a list of some of the most famous stories about Mozart and our verdict on each one!</p>

<p>🎁 <strong>We have a gift for you!</strong> While reading the blog post, you can download the FREE infographic of Mozart life.</p>

<p class="text-md"><a href="#" target="_blank">Learn more about it!</a> 🤓</p>
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'quiz', 
		'color' => 'yellow',
		'link' => '#'
	],
	'title' => 'QUIZ: The basics you should know about Mendelssohn. How well do you know him?'])

<p>Hailed in his own time as the "Second Mozart", Felix Mendelssohn was a child prodigy, virtuoso pianist and great composer. Here are some of the most important facts you should know about him!</p>

<p>How many you can guess???</p>

<p class="text-md text-italic"><strong>DISCLAIMER:</strong> This quiz is very hard, so far it has an average score of 5 out of 9! Can you beat that?</p>

@component('mail::button', ['url' => '#'])
Let me try!
@endcomponent
@endcomponent

@component('mail::newsletter.section', [
	'badge' => [
		'name' => 'tools', 
		'color' => 'pink',
		'link' => '#'
	],
	'title' => 'Scales tutor'])

<p>This tool will help you <strong>learn the notes</strong> and <strong>fingering</strong> for every scale in any key or mode. You will also find information about the different types of minor keys and special modes.</p>

<p>Just select the key and mode you need and we will show you all the resources you need!</p>

<p class="text-md"><a href="#" target="_blank">Look up the scales, click HERE</a></p>
@endcomponent

<h1 class="text-lg mb-4">True or False: Mixed musical knowledge edition</h1>
@include('mail::lists.numbered', ['items' => [
	'<strong>J.S. Bach was born in Germany:</strong> TRUE | FALSE',
	'<strong>A basic chord is made up of 4 notes:</strong> TRUE | FALSE',
	'<strong>The piano has the widest range of tones of all instruments:</strong> TRUE | FALSE',
	'<strong>B to F is a perfect fifth:</strong> TRUE | FALSE'
]])

<div class="text-center mt-4">Find the answers at the bottom of the newsletter!</div>

@include('mail::divider', ['orientation' => 'vertical'])

<h1 class="text-lg mb-4">4 things worth sharing this month:</h1>

@component('mail::panel')
People went wild at Franz Liszt’s concerts. No exaggeration. With his long hair and theatrical style, Liszt played with intensity and passion, revolutionizing the way classical music was performed. And <a href="'#" target="_blank">Lisztomania</a> swept across Europe in the 19th century. <a href="#" target="_blank">Watch this video to learn more</a>
@endcomponent

@component('mail::panel')
ASNJKN askjdnaskdjns asudhduhishiASD
@endcomponent

@component('mail::panel')
On January 1, 2020, <a href="#" target="_blank">works from 1924 will enter the US public domain</a>, where they will be free for all to use and build upon, without permission or fee. These works include George Gershwin’s <i>Rhapsody in Blue</i>, silent films by Buster Keaton and Harold Lloyd, and books such as Thomas Mann’s <i>The Magic Mountain</i>, E. M. Forster’s <i>A Passage to India</i>, and A. A. Milne’s <i>When We Were Very Young</i>.
@endcomponent
@component('mail::panel')
<a href="#" target="_blank">Watch Kobe Bryant playing the Moonlight Sonata</a>, the first movement. He was a basketball star and... a pianist. He loved music and played. 👏👏👏👏👏
@endcomponent

<div class="mb-4 mt-4 text-center">Answers: 1. TRUE, 2. FALSE, 3. TRUE, 4. FALSE</div>

<p>Got any questions? Feel free to reply to this email, there are real people behind it and we'd love to hear from you :)</p>
@include('mail::signature')
@endcomponent
