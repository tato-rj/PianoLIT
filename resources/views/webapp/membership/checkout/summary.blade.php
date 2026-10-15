@component('shop.components.forms.summary')
	<div class="mb-2"><small>You're about to subscribe to the</small></div>
	<div class="mb-3 pb-3 border-bottom">
		<div class="d-flex d-apart mb-4">
			<h4 class="m-0">{{ucfirst($plan->long_name)}}</h4>
			<h4 class="m-0"><strong data-membership-total data-base-price="{{ $plan->price }}">${{$plan->formattedPrice()}}</strong></h4>
		</div>
		<div class="text-center p-1 bg-light mb-4">
			<p class="m-0"><small>{{ $trialDays ? 'Billed after free trial' : 'Billed when your subscription starts' }}</small></p>
		</div>
		<div class="text-center">
            @if($trialDays)
                <p class="text-brand"><strong>You will start with a {{ $trialDays }}-day free trial</strong></p>
                <p class="m-0 text-muted">Cancel during your trial and you <u>won’t be charged</u></p>
            @else
                <p class="m-0 text-muted">Your membership starts after your payment is confirmed.</p>
            @endif
		</div>
	</div>
	<div>
		<p class="m-0 text-muted"><small>Not what you expected?</small></p>
		<a href="{{route('webapp.membership.checkout', $plan->other())}}">Switch to the {{$plan->other()->long_name}}</a>
	</div>
@endcomponent
