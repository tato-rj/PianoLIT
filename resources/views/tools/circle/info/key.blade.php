@component('components.overlays.modal', ['title' => 'Key', 'size' => 'lg'])
	@include('tools.circle.info.title')
	
	<p>In music, a <i>key</i> is a group of notes and chords that revolves or gravitates around a single chord, known as the <strong>tonic</strong>. That attraction is created by the presence of the <strong>leading-tone</strong>: the last note of the scale that is just a half step away from the tonic (the first note). A key also assigns functions to each of its chords: much like in a sentence each word has a specific function (verbs, nouns, and so on), chords in a musical phrase also have functions that give meaning to the group.</p>

	@include('tools.circle.info.link', ['link' => 'https://en.wikipedia.org/wiki/Key_(music)'])

	@include('tools.circle.info.title', ['title' => 'Major and Minor: what\'s the difference?'])
	
	<p>What we refer to as <i>major</i> and <i>minor</i> keys are just 2 out of the 7 greek modes</a>. The basic difference between them is that the 3rd note in the major scale is a major 3rd, whereas on a minor scale it is a minor 3rd. The 6th and the 7th are also different: major on the major scale, minor on the minor scale.</p>

	@include('tools.circle.info.link', ['link' => 'https://en.wikipedia.org/wiki/Mode_(music)'])
@endcomponent