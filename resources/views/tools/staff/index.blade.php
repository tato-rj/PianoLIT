@extends('layouts.app', [
	'title' => 'Staffs Generator | ' . config('app.name'),
	'popup' => ['view' => 'gift'],
	'shareable' => [
		'keywords' => 'staff,music theory,music sheet,piano sheet,treble sheet,bass sheet',
		'title' => 'Staffs Generator',
		'description' => 'Generate staff papers in different formats for free!',
		'thumbnail' => asset('images/misc/thumbnails/staff.jpg'),
		'created_at' => carbon('28-08-2019'),
		'updated_at' => carbon('28-08-2019')
	]])

@section('content')

@pagetitle([
	'version' => '1.0',
	'title' => 'Staff Generator', 
	'subtitle' => 'Download new staff paper in different formats quick and easy'])

<div class="container mb-4">
	<div class="row mb-6">
		<div class="col-lg-8 col-12 mx-auto">
			<div class="row">
				@foreach($files as $file)
				<div class="col-lg-6 col-md-6 col-12 position-relative mb-4">
					<h4 class="text-grey">{{ucfirst($file)}}</h4>
					<img src="{{asset('images/sheets/' . $file . '.png')}}" class="w-100 rounded shadow-sm border cursor-pointer staff">
					<div class="controls position-absolute w-100 h-100" style="display: none; top: 0; left: 0; background-color: rgba(255,255,255,0.6)">
						<div class="d-flex flex-center flex-column w-100 h-100">
							<a href="{{route('tools.staff', ['type' => $file])}}" target="_blank" class="btn btn-teal mb-2 animated fadeInUp shadow" style="display: none;"><i class="fas fa-cloud-download-alt mr-2"></i>Normal</a>
							<a href="{{route('tools.staff', ['type' => $file, 'size' => 'xl'])}}" target="_blank" class="btn btn-teal animated fadeInUp shadow" style="display: none; animation-delay: .15s"><i class="fas fa-cloud-download-alt mr-2"></i>Extra large</a>
						</div>
					</div>
				</div>
				@endforeach
			</div>
		</div>
	</div>
</div>

{{-- @popup(['view' => 'subscription']) --}}
@endsection

@push('scripts')
@addthis

<script type="text/javascript">
$('.staff').on('click', function() {
	$('.staff').next('.controls').fadeOut(function() {
		$(this).find('a').hide();
	});

	$(this).next('.controls').fadeIn('fast', function() {
		$(this).find('a').show();
	});
});
</script>
@endpush