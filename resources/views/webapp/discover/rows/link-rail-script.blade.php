@once
@push('scripts')
<script>
(function () {
    document.querySelectorAll('#discover-composers-rail, .discover-recent__rail, .discover-latest__rail, .discover-for-you__rail').forEach(function (rail) {
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
    });
}());
</script>
@endpush
@endonce
