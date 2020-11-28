<section id="quiz">
	@component('funnels.find-your-match.components.question', ['question' => 'Which music would you listen to during a relaxing Sunday afternoon?'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Goldberg Variations', 'subtitle' => 'by J.S.Bach'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Romeo and Juliet', 'subtitle' => 'by P.Tchaikovsky'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Moonlight Sonata', 'subtitle' => 'by L.V.Beethoven'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Requiem in D minor', 'subtitle' => 'by W.A.Mozart'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Firebird Suite', 'subtitle' => 'by I.Stravinsky'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Nocturne in C# minor', 'subtitle' => 'by F.Chopin'])
	@endcomponent

	@component('funnels.find-your-match.components.question', ['question' => 'Which heart-melting theme do you like the most?'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Rachmanioff\'s 2nd Piano Concerto', 'audio' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Chopin\'s 1st Ballade'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Mahler\'s Symphonies'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Mozart\'s Piano Concertos'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Wagner\'s Operas'])
		@include('funnels.find-your-match.components.answer', ['label' => 'Prokofiev\'s Sonatas'])
	@endcomponent
</section>