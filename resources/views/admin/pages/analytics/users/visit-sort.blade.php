@component('admin.components.filters.form', [
    'id' => 'visit-sorting', 'label' => 'Visit sorting',
    'hidden' => request()->except(['visit_sort', 'visit_order', 'visits_page']),
    'fields' => [['name' => 'visit_sort', 'label' => 'Sort visits', 'options' => \App\Activity\VisitSort::OPTIONS]],
    'state' => ['visit_sort' => $visitSort->field],
    'buttons' => [
        ['label' => 'Apply filters', 'name' => 'visit_order', 'value' => $visitSort->order],
        ['label' => 'Reverse order', 'name' => 'visit_order', 'value' => $visitSort->reverseOrder(), 'secondary' => true, 'class' => 'ua-order-toggle', 'icon' => $visitSort->order === 'asc' ? 'arrow-up' : 'arrow-down', 'title' => $visitSort->orderLabel().'. Switch to '.$visitSort->orderLabel($visitSort->reverseOrder()), 'ariaLabel' => 'Reverse order: '.$visitSort->orderLabel($visitSort->reverseOrder())],
    ],
])
    @slot('note')
        @if($visitSort->byActions())<span class="admin-filter-note">Actions count the colored tags.</span>@endif
    @endslot
@endcomponent
