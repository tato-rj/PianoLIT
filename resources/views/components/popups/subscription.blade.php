@component('components.modal', ['id' => 'modal-subscription', 'options' => ['header' => ['raw' => true]]])
@slot('header')
<h5 class="mt-2">@fa(['icon' => 'envelope', 'color' => 'primary'])Don't miss out!</h5>
<div style="margin-bottom: -8px">Sign up for our newsletter to stay in the loop.</div>
@endslot

@slot('body')
<div class="text-center">
	@include('components.form.subscription', ['id' => 'subscription-form', 'label' => 'SUBSCRIBE NOW'])
</div>
@endslot
@endcomponent