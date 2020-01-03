<section>
	@if($policy->has('instrument_type'))
		<p class="section-title">Instrument</p>

		@if(in_array('acoustic_piano', $policy->get('instrument_type')))
			<p>I recommend an acoustic piano for my students. It is impossible to replace the sound and touch of an acoustic piano and the vibrations of the strings create harmonic overtones that sound simultaneously, creating a full and rich sound. The physical reaction you get from the keys in an acoustic piano is not just about the weight: all the tiny parts within the instrument move in sync to produce the sound, creating a direct link between finger motion and sound production. This may seem subtle, but it does make a different in both technical and ear development.</p>
		@endif

		@if(in_array('digital_piano', $policy->get('instrument_type')))
			<p>{{in_array('acoustic_piano', $policy->get('instrument_type')) ? 'If an acoustic piano is not an option at the moment, I can also' : 'I'}} recommend a good digital piano. They have come a long way in recent years and may be good substitutes for an acoustic piano.</p>
			<p><u>Why digital pianos might be good choice?</u></p>
			<ul>
				<li>They take up less space if compared to a grand or even upright piano.</li>
				<li>They are are more affordable: 1K to 5K as opposed to 10K for a baby grand piano, for example.</li>
				<li>They are always in tune and require little maintenance.</li>
				<li>They include a variety of nice features: recording options, use of headphones for private practice, different instrumental sounds, etc.</li>
			</ul>
		@endif

		@if(in_array('digital_piano', $policy->get('instrument_type')))
			<p><u>Requirements for a digital piano:</u></p>
			<ul>
				<li>Full range (88 keys)</li>
				<li>Fully weighted keys</li>
				<li>Equipped with music stand and pedal</li>
				<li>Set on a sturdy stand or table at proper height (about 28” from floor)</li>
				<li>Bench at a height that keeps forearms level while playing</li>
			</ul>
			<p>A digital piano that doesn't meet these requirements will <u>not</u> work for piano lessons.</p>
		@endif
	@endif

	@if($policy->has('instrument_accessories'))
		<p class="section-title">Accessories</p>
		<p>I provide the following {{str_plural('accessory', $policy->count('instrument_accessories'))}} for every lesson:</p>
		<ul>
			@foreach($policy->get('instrument_accessories') as $accessory)
			<li>{{$accessory}}</li>
			@endforeach
		</ul>
	@endif	
</section>