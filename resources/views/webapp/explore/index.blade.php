@extends('webapp.layouts.app', ['title' => 'Explore', 'hideMobileMenu' => $hasSelection && $guide])

@push('header')
<style>
@media (max-width: 991.98px) and (prefers-reduced-motion: no-preference) {
    @view-transition { navigation: auto; }
}
</style>
<script src="{{ mix('js/views/explore-transitions.js') }}"></script>
@endpush

@if($hasSelection && $guide)
@push('page-navigation')
<div class="explore-mobile-navigation d-lg-none bg-light border-bottom">
    @include('webapp.explore.path', ['pathClasses' => 'p-3'])
</div>
@endpush
@endif

@section('content')
<div @if($hasSelection) class="d-none d-lg-block" @endif>
@include('webapp.layouts.header', ['title' => 'Explore', 'subtitle' => 'Find your way through the repertoire.'])
<section class="mb-4">
    @include('webapp.search.form', ['searchPlaceholder' => 'Know the title or composer? Search here...', 'accessibleSearch' => true])
</section>
</div>

<div id="explore-catalogue" class="explore-layout {{ $hasSelection ? 'has-selection' : '' }}">
    <nav class="explore-directory" aria-label="Explore repertoire">
        <h4 class="mb-2">Start with</h4>
        @include('webapp.explore.directory')
    </nav>
    <section class="explore-content" aria-label="Explore your selection">
        @if($guide)
            @include('webapp.explore.level')
        @else
            <h3>Find your next piece</h3>
            <p class="text-muted">Choose a mood, technique, composer or style to explore the repertoire.</p>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
let recent = app.user ? getRecent() : [];

showRecent();
$('input[name="search"]').keyup(function() {
	let length = $(this).val().length;

	if (length > 3) {
		$('[data-erase]').show();
	} else {
		$('[data-erase]').hide();
	}
});
$('[data-erase]').click(function() {
    let name = $(this).data('erase');
    $('[name="'+name+'"]').val('');
    $(this).hide();
});

$('#search-form').on('submit', function() {
	let query = $(this).find('input[name="search"]').val();
	saveRecent(query, recent);
});

$('.recent-query').on('click', function() {
	submitRecent($(this).text());
});

function getRecent() {
	let cookie = getCookie('pl_recent');

	if (typeof cookie === 'undefined' || cookie === null)
		return [];

	try {
		let saved = JSON.parse(cookie || '[]');
		return Array.isArray(saved) ? saved.filter(query => typeof query === 'string' && query.trim().length > 0 && query.length <= 18).slice(0, 5) : [];
	} catch (error) {
		return [];
	}
}

function showRecent() {
	if (recent.length) {
		let $recentContainer = $('#most-recent');

		for (let i=0; i< recent.length; i++) {
			$recentContainer.find('> div').append('<span class="recent-query cursor-pointer m-1 rounded-pill border border-grey px-2"><small style="line-height: 2"><i class="app-icon icon-search icon-size-sm text-muted me-1"></i>'+$('<span>').text(recent[i]).html()+'</small></span>');
		}

		$recentContainer.show();
	}
}

function saveRecent(query, recent) {
	if (! app.user) return;

	if (! recent.includes(query) && query.length <= 18)
		recent.unshift(query);

	if (recent.length > 5)
		recent.pop();

	setCookie('pl_recent', JSON.stringify(recent), 30);
}

function submitRecent(recent) {
	let $form = $('#search-form');
	$form.find('input[name="search"]').val(recent);
	$form.submit();
}
</script>

<script>
document.querySelectorAll('.explore-directory, .explore-content').forEach(function (column) {
    column.querySelectorAll('details').forEach(function (section) {
        section.addEventListener('toggle', function () {
            if (!section.open) return;
            column.querySelectorAll('details').forEach(function (other) {
                // Keep a nested section's parent open so its contents stay reachable.
                if (other !== section && !other.contains(section)) other.open = false;
            });
        });
    });
});
</script>
@endpush
