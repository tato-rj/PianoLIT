@extends('layouts.app', ['title' => 'Studio Policy Generator | ' . config('app.name')])

@push('header')
<style type="text/css">
.card.selected h6.number {
    background-color: #4dc0b5!important;
    color: #fff!important;
}
.card.selected h6.title {
	color: #212529!important;
}
</style>
@endpush

@section('content')

<div class="container mb-4">
	<div class="row mb-6">
		<div class="col-lg-8 col-12 mx-auto">
			<div class="mb-3">
				<h4>Update my policy</h4>
				<div class="d-flex d-apart">
					<p class="text-right text-muted m-0">
						<small><i class="fas fa-calendar-alt mr-1"></i>last updated on {{$studioPolicy->updated_at->toFormattedDateString()}} at {{$studioPolicy->updated_at->format('g:i A')}}</small>
					</p>
					<a class="btn btn-sm btn-teal-outline" href="{{route('users.studio-policies.show', $studioPolicy->id)}}">
						<i class="fas fa-file-download mr-2"></i>Download policy
					</a>
				</div>
			</div>
			<form method="POST" action="{{route('users.studio-policies.update', $studioPolicy->id)}}">
				@method('PATCH')
				@csrf
				<div class="accordion mb-4" id="steps">
					@include('users.studio-policies.create.form')
				</div>
				<div class="text-center">
					<button class="btn btn-primary shadow"><i class="fas fa-save mr-2"></i>Save my changes</button>
					<div class="mt-3">
						@include('components.return', ['url' => route('users.studio-policies.index'), 'to' => 'my policies page'])
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
<script type="text/javascript">
$('#steps').on('show.bs.collapse', function (step) {
	$('#steps .card').removeClass('selected');
	$(step.target).parent().addClass('selected');
})
</script>
@endpush