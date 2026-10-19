@if($preview ?? false)
<div class="admin-filter-form {{ $class ?? '' }}" id="{{ $id }}" aria-label="{{ $label }}" data-admin-filter-form>
@else
<form class="admin-filter-form {{ $class ?? '' }}" method="GET" action="{{ $action ?? request()->url() }}" id="{{ $id }}" aria-label="{{ $label }}" data-admin-filter-form>
    @include('admin.components.filters.hidden', ['values' => $hidden ?? [], 'prefix' => null])
@endif
    @include('admin.components.filters.fields', ['fields' => $fields ?? [], 'state' => $state ?? []])
    {{ $slot ?? '' }}
    @foreach($buttons ?? [['label' => 'Apply filters']] as $filterButton)
        @include('admin.components.filters.button', ['button' => $filterButton, 'preview' => $preview ?? false])
    @endforeach
    @if(!empty($resetUrl))<a class="admin-filter-reset" href="{{ $resetUrl }}">Reset</a>@endif
    {{ $note ?? '' }}
@if($preview ?? false)</div>@else</form>@endif
