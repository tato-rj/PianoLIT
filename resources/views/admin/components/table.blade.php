@php
    $items = $items ?? collect();
    $paginated = $items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    $pageName = $paginated ? $items->getPageName() : 'page';
    $state = $state ?? [];
    $filters = $filters ?? [];
    $sorts = $sorts ?? [];
    $pageSizes = $pageSizes ?? \App\Support\AdminPagination::PAGE_SIZES;
    $showPageSize = $paginated && count($pageSizes) && ($items->total() > \App\Support\AdminPagination::PER_PAGE || $items->perPage() !== \App\Support\AdminPagination::PER_PAGE);
    $action = $action ?? url()->current();
    $showReset = $showReset ?? (bool) array_filter(request()->except($pageName));
@endphp
<section class="admin-table {{ $class ?? '' }}" @if(!empty($title)) aria-labelledby="{{ $id }}-title" @else aria-label="{{ $label ?? 'Results' }}" @endif>
    @if(!empty($title) || !empty($description))
    <div class="admin-table__heading">
        @if(!empty($title))<h2 id="{{ $id }}-title">{{ $title }}</h2>@endif
        @if(!empty($description))<p>{{ $description }}</p>@endif
    </div>
    @endif
    @if(isset($search) || count($filters) || count($sorts) || $showPageSize)
    <form class="admin-table__filters" method="GET" action="{{ $action }}" aria-label="{{ $label ?? $title ?? 'Results' }} filters">
        @foreach($hidden ?? [] as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
        @if(isset($search))
        @input(['bag' => 'default', 'id' => $id.'-search', 'name' => 'search', 'type' => 'search', 'label' => $search['label'] ?? 'Search', 'value' => $state['search'] ?? '', 'placeholder' => $search['placeholder'] ?? 'Search results', 'limit' => $search['maxlength'] ?? 255, 'required' => false, 'grid' => 'admin-table__search'])
        @endif
        @foreach($filters as $filter)
        @select(['bag' => 'default', 'id' => $id.'-'.$filter['name'], 'name' => $filter['name'], 'label' => $filter['label'], 'choices' => $filter['options'], 'value' => $state[$filter['name']] ?? '', 'required' => false])
        @endforeach
        @if(count($sorts))
        @select(['bag' => 'default', 'id' => $id.'-sort', 'name' => 'sort', 'label' => 'Sort by', 'choices' => $sorts, 'value' => $state['sort'] ?? '', 'required' => false])
        @endif
        @if($showPageSize)
        @select(['bag' => 'default', 'id' => $id.'-per-page', 'name' => 'per_page', 'label' => 'Per page', 'choices' => array_combine($pageSizes, $pageSizes), 'value' => $items->perPage(), 'required' => false])
        @endif
        <button class="admin-table__button" type="submit">Apply filters</button>
        @if($showReset)<a class="admin-table__reset" href="{{ $resetUrl ?? $action }}">Reset</a>@endif
    </form>
    @endif
    {{ $toolbar ?? '' }}
    <p class="admin-table__scroll-hint">Scroll sideways to see all columns.</p>
    <div class="admin-table__scroll" tabindex="0" role="region" aria-label="{{ $label ?? $title ?? 'Table' }} results">
        <table id="{{ $id }}" class="admin-table__table {{ $tableClass ?? '' }}" aria-label="{{ $label ?? $title ?? 'Results' }}">
            <thead><tr>
                @foreach($columns as $column)
                @php
                    $column = is_array($column) ? $column : ['label' => $column];
                    $direction = array_search($state['sort'] ?? '', $column['sort'] ?? [], true);
                    $nextSort = ($column['sort'] ?? [])[$direction === 'asc' ? 'desc' : 'asc'] ?? null;
                @endphp
                <th scope="col" class="{{ $column['class'] ?? '' }}" @if(!empty($column['sort'])) aria-sort="{{ $direction === 'asc' ? 'ascending' : ($direction === 'desc' ? 'descending' : 'none') }}" @endif>
                    @if($nextSort)
                    <a href="{{ $action.'?'.http_build_query(array_merge(request()->except($pageName), ['sort' => $nextSort])) }}" class="admin-table__sort">
                        {{ $column['label'] }}<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : ($direction === 'desc' ? '↓' : '↕') }}</span><span class="visually-hidden">, sort {{ $direction === 'asc' ? 'descending' : 'ascending' }}</span>
                    </a>
                    @else<span @if(!empty($column['hidden'])) class="visually-hidden" @endif>{{ $column['label'] }}</span>@endif
                </th>
                @endforeach
            </tr></thead>
            <tbody>
                @if($items->count()){{ $slot }}@else
                <tr><td colspan="{{ count($columns) }}"><div class="admin-table__empty">
                    <h3>{{ $emptyTitle ?? 'No matching results' }}</h3>
                    @if(!empty($emptyDescription))<p>{{ $emptyDescription }}</p>@endif
                </div></td></tr>
                @endif
            </tbody>
        </table>
    </div>
    @if($paginated && $items->count())
    <div class="admin-table__footer">
        <p>Showing {{ number_format($items->firstItem()) }}–{{ number_format($items->lastItem()) }} of {{ number_format($items->total()) }} results</p>
        {{ $items->onEachSide(1)->links('admin.components.table-pagination', ['label' => $label ?? $title ?? 'Results']) }}
    </div>
    @elseif($paginated && $items->hasPages())
    <div class="admin-table__footer">{{ $items->onEachSide(1)->links('admin.components.table-pagination', ['label' => $label ?? $title ?? 'Results']) }}</div>
    @endif
</section>
