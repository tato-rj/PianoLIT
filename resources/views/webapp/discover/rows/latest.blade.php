<section class="discover-latest mb-4" aria-labelledby="latest-pieces-heading">
	<div class="d-flex d-apart mb-3">
		<h5 id="latest-pieces-heading" class="m-0">{{ $row['title'] }}</h5>
		<a href="{{ route('webapp.latest') }}" class="btn-raw link-primary d-inline-flex align-items-center gap-2">View all @icon('arrow-right', ['mr' => 0])</a>
	</div>
	<div class="discover-latest__rail custom-scroll dragscroll dragscroll-horizontal card-scroll-row">
		<div class="discover-latest__items d-flex gap-3 pb-2">
			@foreach($row['content'] as $piece)
				@include('webapp.discover.cards.latest-piece')
			@endforeach
		</div>
	</div>
</section>
@include('webapp.discover.rows.link-rail-script')
