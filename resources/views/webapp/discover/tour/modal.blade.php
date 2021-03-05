@component('components.modal', [
	'id' => 'tour-modal',

])
@slot('body')
<div class="mb-2">
	@include('funnels.find-your-match.carousel')
</div>
@endslot

@endcomponent