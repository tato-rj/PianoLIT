@component('components.overlays.modal', ['title' => 'Key', 'size' => 'lg'])
	@include('tools.circle-of-fifths.info.title')
	
	<p>In music, a <i>key</i> is a group of notes and chords that revolves or gravitates around a single chord, known as the <strong>tonic</strong>. That attraction is created by the presence of the major 7th, also called the <strong>leading-tone</strong>: the last note of the scale that is just a half step away from the tonic (the first note). A key also assigns functions to each of its chords: much like in a sentence each word has a specific function (verbs, nouns, and so on), chords in a musical phrase also have functions that give meaning to the group.</p>

	@include('tools.circle-of-fifths.info.link', ['link' => 'https://en.wikipedia.org/wiki/Key_(music)'])

	@include('tools.circle-of-fifths.info.title', ['title' => 'Major and Minor: what\'s the difference?'])
	
	<p>The basic difference between them is that the <u>3rd note in the major scale is a major 3rd</u>, whereas <u>on a minor scale it is a minor 3rd</u>. The 6th and the 7th are also different: major on the major scale, minor on the minor scale.</p>

	@include('tools.circle-of-fifths.info.link', ['link' => 'https://en.wikipedia.org/wiki/Mode_(music)'])

	@include('tools.circle-of-fifths.info.title', ['title' => 'What does Enharmonic mean?'])
	
	<p>Two keys are <i>enharmonic</i> when their notes share the same pitches but are named differently. Not only keys can be enharmonic, but also chords or single notes. For example, the note C is enharmonic with B#: on the piano, you will find them both in the same key, but their names are different.</p>

	@include('tools.circle-of-fifths.info.link', ['link' => 'https://en.wikipedia.org/wiki/Enharmonic'])
@endcomponent