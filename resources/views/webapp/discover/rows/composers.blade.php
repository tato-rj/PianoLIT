<div class="discover-composers mb-4">
	<div class="d-flex d-apart mb-3">
		<h5 class="m-0">Composers</h5>
		<a href="{{route('webapp.composers.index')}}" class="btn-raw link-primary d-inline-flex align-items-center gap-2">View all @icon('arrow-right', ['mr' => 0])</a>
	</div>

	<div class="discover-composers__gallery position-relative">
		<div class="collections-book-controls discover-composers__previous" hidden>
			<button type="button" data-composers-previous aria-label="Previous composers" aria-controls="discover-composers-rail" disabled>@icon('chevron-left', ['mr' => 0])</button>
		</div>
		@include('webapp.components.grids.circles', [
		'collection' => $row['content'],
		'composerLinks' => true,
		'composerRow' => true,
		'name' => 'name',
		'image' => 'cover_image',
		'count' => 'pieces_count'])
		<div class="collections-book-controls discover-composers__next" hidden>
			<button type="button" data-composers-next aria-label="Next composers" aria-controls="discover-composers-rail">@icon('chevron-right', ['mr' => 0])</button>
		</div>
	</div>
</div>

@push('scripts')
<script>
(function () {
    var rail = document.getElementById('discover-composers-rail');
    if (!rail) return;
    var row = rail.closest('.discover-composers');
    var previous = row.querySelector('[data-composers-previous]');
    var next = row.querySelector('[data-composers-next]');
    function update() {
        var maximum = rail.scrollWidth - rail.clientWidth;
        previous.parentElement.hidden = next.parentElement.hidden = maximum <= 1;
        previous.disabled = rail.scrollLeft <= 1;
        next.disabled = rail.scrollLeft >= maximum - 1;
    }
    function move(direction) {
        var cards = rail.querySelectorAll('.composer-tile');
        if (cards.length < 2) return;
        var distance = cards[1].offsetLeft - cards[0].offsetLeft;
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        rail.scrollBy({left: direction * distance, behavior: reduceMotion ? 'auto' : 'smooth'});
    }
    previous.addEventListener('click', function () { move(-1); });
    next.addEventListener('click', function () { move(1); });
    var dragStart = null;
    var dragged = false;
    rail.addEventListener('mousedown', function (event) { dragStart = event.clientX; dragged = false; });
    window.addEventListener('mousemove', function (event) {
        if (dragStart !== null && Math.abs(event.clientX - dragStart) > 4) dragged = true;
    });
    window.addEventListener('mouseup', function () { dragStart = null; });
    rail.addEventListener('click', function (event) {
        if (event.detail && dragged) event.preventDefault();
    });
    rail.addEventListener('scroll', update, {passive: true});
    window.addEventListener('resize', update);
    update();
}());
</script>
@endpush
