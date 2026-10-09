@extends('webapp.layouts.app', ['title' => 'Explore'])

@section('content')
<div @if(request()->filled('level')) class="d-none d-lg-block" @endif>
@include('webapp.layouts.header', ['title' => 'Explore', 'subtitle' => 'Find your way through the repertoire.'])
<section class="mb-4">
    @include('webapp.search.form', ['searchPlaceholder' => 'Know the title? Search here...', 'accessibleSearch' => true])
</section>
</div>

<div id="explore-catalogue" class="explore-layout {{ request()->filled('level') ? 'has-selection' : '' }}">
    <nav class="explore-directory" aria-label="Explore repertoire">
        <h4 class="mb-2">Start with</h4>
        @include('webapp.explore.directory')
    </nav>
    <section class="explore-content" aria-label="Explore within a level">
        @if($selected)
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
document.querySelectorAll('.explore-directory > details').forEach(function (section) {
    section.addEventListener('toggle', function () {
        if (!section.open) return;
        document.querySelectorAll('.explore-directory > details').forEach(function (other) {
            if (other !== section) other.open = false;
        });
    });
});
</script>
@endpush
