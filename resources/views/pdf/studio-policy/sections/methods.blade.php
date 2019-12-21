@if($policy->has('methods'))
<section>
	<p class="section-title">Teaching methods</p>
	<p>In adition to the traditional practices of teaching, I also teach using the following {{str_plural('method', $policy->count('methods'))}}:</p>
	<ul>
		@foreach($policy->get('methods') as $method)
		<li>{{$method}}</li>
		@endforeach
	</ul>

	@if($policy->has('other_methods'))
	<p>{{$policy->get('other_methods')}}</p>
	@endif
</section>
@endif