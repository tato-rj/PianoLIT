<form class="download-library-toolbar row g-3 align-items-end mb-4" method="GET" action="{{ route('users.purchases') }}" role="search" aria-label="Search your downloads">
    <div class="col-12 col-md">
        <div class="row g-2">
            <div class="col">
                <label class="visually-hidden" for="downloads-search">Search downloads</label>
                @input(['id' => 'downloads-search', 'name' => 'q', 'type' => 'search', 'value' => $filters['q'], 'placeholder' => 'Search downloads', 'required' => false, 'limit' => 150])
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-secondary">@icon('search')Search</button></div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <label class="visually-hidden" for="downloads-type">Resource type</label>
        @select(['bag' => 'default', 'id' => 'downloads-type', 'name' => 'type', 'value' => $filters['type'], 'choices' => ['' => 'All types'] + $library::LABELS, 'required' => false, 'submitOnChange' => true])
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <label class="visually-hidden" for="downloads-sort">Sort downloads</label>
        @select(['bag' => 'default', 'id' => 'downloads-sort', 'name' => 'sort', 'value' => $filters['sort'], 'choices' => ['newest' => 'Newest first', 'oldest' => 'Oldest first'], 'required' => false, 'submitOnChange' => true])
    </div>
    @if($filters['q'] !== '' || $filters['type'] !== '')
        <div class="col-auto align-self-center"><a href="{{ route('users.purchases') }}">Clear</a></div>
    @endif
</form>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>@paginationCount(['collection' => $purchases, 'align' => 'left', 'item' => 'resource'])</div>
    <div class="download-library-views">
        @include('components.forms.segmented-control', ['id' => 'downloads-view', 'name' => 'downloads-view', 'label' => 'Library view', 'options' => ['list' => 'List', 'small' => 'Small icons', 'large' => 'Large icons'], 'value' => 'large', 'dataAttribute' => 'download-view'])
    </div>
</div>
