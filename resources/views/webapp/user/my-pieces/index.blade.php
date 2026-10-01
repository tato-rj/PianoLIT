@extends('webapp.layouts.app', ['title' => 'My pieces'])

@section('content')
@guest('web')
    @include('webapp.layouts.header', ['title' => 'My Pieces'])
    @include('webapp.components.sign-in')
@else
    @component('webapp.layouts.header', ['title' => 'My Pieces', 'subtitle' => 'Your favorite music, beautifully organized.'])
        <div class="my-pieces-switch nav" role="tablist" aria-label="My pieces sections">
            <a class="active" id="favorites-tab" data-bs-toggle="tab" href="#list-favorites" role="tab" aria-controls="list-favorites" aria-selected="true">FAVORITES</a>
            <a id="suggestions-tab" data-bs-toggle="tab" href="#list-uploads" role="tab" aria-controls="list-uploads" aria-selected="false">SUGGESTIONS</a>
        </div>
    @endcomponent

    <div class="my-pieces-page tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="list-favorites" role="tabpanel" aria-labelledby="favorites-tab">
            @include('webapp.user.my-pieces.favorites.index')
        </div>
        <div class="tab-pane fade" id="list-uploads" role="tabpanel" aria-labelledby="suggestions-tab">
            @include('webapp.user.my-pieces.suggestions')
        </div>
    </div>
@endguest
@endsection

@push('scripts')
@auth('web')
<script src="{{ mix('js/views/folders.js') }}"></script>
<script type="text/javascript">
$('#local-filter input[type="checkbox"]').change(function() {
    let filters = [];

    $('#local-filter .options-columns > div').each(function() {
        let arr = $(this).find('input[type="checkbox"]:checked').attrToArray('value');

        if (arr.length)
            filters.push(arr);
    });

    reset();

    if (filters.length)
        applyFilters(filters);
});

function reset() {
    $('.piece-result').show();
}

function applyFilters(filters) {
    $('.piece-result').hide();

    $('.piece-result').each(function() {
        let tags = $(this).data('tags');
        let valid = 0;

        for (let i = 0; i < filters.length; i++) {
            if (filters[i].some(tags.includes.bind(tags)))
                valid += 1;
        }

        if (valid == filters.length) $(this).show();
    });
}
</script>
@endauth
@endpush
