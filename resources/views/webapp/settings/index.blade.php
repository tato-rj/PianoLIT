@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Settings', 'subtitle' => 'Control your profile and membership preferences'])

<div class="row">
	<div class="col-lg-8 col-12 mx-auto p-0 list-group">
		@if(auth()->user()->isSuperUser())
		@include('webapp.settings.item', ['label' => 'You\'re a super user!'])
		@elseif(auth()->user()->membership()->exists() && ! auth()->user()->membership->source->isEnded())
		@include('webapp.settings.item', ['url' => route('webapp.membership.edit'), 'label' => 'My membership ' . auth()->user()->membership->source->badge()])
		@else
		@include('webapp.settings.item', ['url' => route('webapp.membership.pricing'), 'label' => 'Subscribe <i class="fas fa-star text-yellow"></i>'])
		@endif
		@include('webapp.settings.item', ['url' => route('users.profile'), 'label' => 'My profile'])
		@include('webapp.settings.item', ['url' => route('contact'), 'label' => 'Contact us'])
		@include('webapp.settings.item', ['url' => config('app.url'), 'label' => 'Visit the website'])
		@include('webapp.settings.item', ['url' => route('terms'), 'label' => 'Terms of service'])
		@include('webapp.settings.item', ['url' => route('privacy'), 'label' => 'Privacy policy'])
		<a href="" class="list-group-item py-4 rounded-0 list-group-item-action" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
			<form id="logout-form" action="{{ route('webapp.logout') }}" method="POST" style="display: none;">@csrf</form>Log out
		</a>
	</div>
</div>

@endsection

@push('scripts')
@endpush