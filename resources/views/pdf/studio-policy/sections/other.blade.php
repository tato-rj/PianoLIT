@if($policy->has('other_considerations'))
<section>
	<p class="section-title">Other considerations</p>
	<p>{{$policy->get('other_considerations')}}</p> 
</section>
@endif