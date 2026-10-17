@foreach($suggestions as $piece)
	@include('webapp.components.piece', compact('hasFullAccess'))
@endforeach


@if(!app(\App\Services\WebApp\AccessPolicy::class)->available('personalization', auth('web')->user()))
<div class="text-center py-4"><p>Personal history and suggestions are currently locked.</p>@include('webapp.components.access-action', ['accessFeature' => 'personalization'])</div>
@endif
