@component('components.overlays.modal', ['title' => 'Key', 'size' => 'lg', 'feedback' => true])
	@include('resources.circle.info.title')
	
	<p>In music a <i>key</i> is a group notes and chords that revolves or gravitates around a single chord, known as the <strong>tonic</strong>. That attraction is created by the presence of the <strong>leading-tone</strong>: the last note of the scale that is just a half step away from the tonic (the first note). A key also assigns functions to each of its chords: much like in a sentence each word has a specific function (verbs, nouns, and so on), chords in a musical phrase also have functions that give meaning to the group.</p>

	@include('resources.circle.info.link')

	@include('resources.circle.info.title', ['title' => 'Major and Minor: what\'s the difference?'])
	
	<p>What we refer to as <i>major</i> and <i>minor</i> keys are just 2 out of the <a href="#" target="_blank" class="link-blue" title="Lean more about this">7 greek modes</a>. The basic difference between them is that the 3rd note in the major scale is a major 3rd whereas in a minor scale it is a minor 3rd.</p>

	@include('resources.circle.info.link')
@endcomponent