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
    $tableFilterFields = [];
    if (isset($search)) $tableFilterFields[] = ['type' => 'search', 'name' => 'search', 'label' => $search['label'] ?? 'Search', 'placeholder' => $search['placeholder'] ?? 'Search results', 'maxlength' => $search['maxlength'] ?? 255];
    foreach ($filters as $filter) $tableFilterFields[] = $filter;
    if (count($sorts)) $tableFilterFields[] = ['name' => 'sort', 'label' => 'Sort by', 'options' => $sorts];
    if ($showPageSize) $tableFilterFields[] = ['name' => 'per_page', 'label' => 'Per page', 'options' => array_combine($pageSizes, $pageSizes), 'value' => $items->perPage()];
@endphp
<section class="admin-table {{ $class ?? '' }}" @if(!empty($title)) aria-labelledby="{{ $id }}-title" @else aria-label="{{ $label ?? 'Results' }}" @endif>
    @if(!empty($title) || !empty($description))
    <div class="admin-table__heading">
        @if(!empty($title))<h2 id="{{ $id }}-title">{{ $title }}</h2>@endif
        @if(!empty($description))<p>{{ $description }}</p>@endif
    </div>
    @endif
    @if(isset($search) || count($filters) || count($sorts) || $showPageSize)
    @include('admin.components.filters.form', ['id' => $id.'-filters', 'label' => ($label ?? $title ?? 'Results').' filters', 'class' => 'admin-table__filters', 'action' => $filterAction ?? $action, 'fields' => $tableFilterFields, 'state' => $state, 'hidden' => $hidden ?? [], 'buttons' => [['label' => 'Apply filters']], 'resetUrl' => $showReset ? ($resetUrl ?? $action) : null, 'slot' => $filterFields ?? null, 'note' => $filterNote ?? null])
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
                @include('admin.components.table-empty-row')
                @endif
            </tbody>
        </table>
    </div>
    @if($paginated)@include('admin.components.pagination', ['paginator' => $items, 'label' => $label ?? $title ?? 'Results'])@endif
</section>
