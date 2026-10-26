@php($isStandardTable = $standard ?? false)
@include('admin.components.filters.datatable', ['tableId' => $table.'-table'])
@if($isStandardTable)
  @component('admin.components.datatable-frame', ['tableId' => $table.'-table'])
    @include('admin.components.datatables.table')
  @endcomponent
@else
  @include('admin.components.datatables.table')
@endif
