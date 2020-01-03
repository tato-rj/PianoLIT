@if($policy->has('materials') || $policy->has('other_materials') || $policy->has('materials_buyer'))
<section>
	<p class="section-title">Materials</p>
	@if($policy->has('materials'))
	<p>Each student uses {{arrayToSentence($policy->get('materials'))}}.</p> 
	@endif
	
	@if($policy->has('other_materials'))
	<p>{{$policy->get('other_materials')}}{{str_ends_with($policy->get('other_materials'), ['.', '!']) ? null : '.'}}</p> 
	@endif
	
	@if($policy->has('materials_buyer'))
	<p><u>{{$policy->get('materials_buyer')}}</u>.</p>
	@endif
	
	@if($policy->has('materials') && in_array('assignment notebooks', $policy->get('materials')))
	<p>The assignment notebook is where I will write what we did in each class and what the student needs to practice during the week at home. I will check on the assignment notebook at each meeting to follow up on what we worked on in the previous lesson.</p>
	@endif
</section>
@endif
