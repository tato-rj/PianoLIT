@extends('layouts.app', [
	'title' => 'Studio Policy Generator | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'studio policy,private studio agreement,private teacher,music teacher policy',
		'title' => 'Studio Policy Generator',
		'description' => 'Generate your studio policy in just a few seconds!',
		'thumbnail' => asset('images/misc/thumbnails/staff.jpg'),
		'created_at' => carbon('13-12-2019'),
		'updated_at' => carbon('13-12-2019')
	]])

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
@include('components.title', [
	'version' => '1.0',
	'title' => 'Studio Policy Generator', 
	'subtitle' => 'Generate your studio policy in just a few seconds!'])

	<div class="container mb-4">
		<div class="row mb-6">
			<div class="col-lg-8 col-12 mx-auto">
				<form method="POST" action="{{route('users.studio-policies.store')}}">
					@csrf
					<div class="accordion mb-4" id="steps">
						@foreach(['general', 'tuition', 'communication'] as $step)
						@include('studio-policies.create.steps.' . $step, ['loop' => $loop])
						@endforeach
					</div>
					<div class="text-center">
						<button class="btn btn-primary shadow"><i class="fas fa-save mr-2"></i>Save and download</button>
					</div>
				</form>
			</div>
		</div>
	</div>

@endsection

@push('scripts')
@include('components.addthis')

<script type="text/javascript">
$('#steps').on('show.bs.collapse', function (step) {
	$('#steps .card').removeClass('selected');
	$(step.target).parent().addClass('selected');
})
</script>
@endpush