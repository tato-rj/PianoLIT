<section>
	<p class="bold">MATERIALS</p>
	@if($policy->has('materials'))
	<p>Each student uses {{arrayToSentence($policy->get('materials'))}}.</p> 
	@endif
	
	@if($policy->has('other_materials'))
	<p>{{$policy->get('other_materials')}}{{str_ends_with($policy->get('other_materials'), ['.', '!']) ? null : '.'}}</p> 
	@endif
	
	<p><u>{{$policy->get('materials_buyer') ? 
			'I purchase the materials in advance and include the cost in the monthly invoice' : 
			'Students are responsible for buying their own materials'}}</u>.</p>
	
	@if(in_array('assignment notebooks', $policy->get('materials')))
	<p>The assignment notebook is where I will write what we did in each class and what the student needs to practice during the week at home. I will check on the assignment notebook at each meeting to follow up on what we worked on in the previous lesson.</p>
	@endif
</section>