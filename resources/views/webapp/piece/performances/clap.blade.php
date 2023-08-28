<div class="d-apart px-2">
	<label class="m-0"><strong>{!! $performance->user->countryFlag !!}{{$performance->displayName()}}</strong></label>

	<div class="d-flex align-items-center">
		<label class="mr-1 mb-0 text-muted claps-count font-weight-bold animated">{{$performance->claps_sum}}</label>
		<button class="btn-raw btn-lg text-grey position-relative clap" data-url="{{route('api.users.performances.clap', $performance)}}">@fa(['icon' => 'hands-clapping', 'mr' => 0])</button>
	</div>
</div>