@php($isStandardTable = $standard ?? false)
@include('admin.components.filters.datatable', ['tableId' => $table.'-table'])
@if($isStandardTable)
  @component('admin.components.datatable-frame', ['tableId' => $table.'-table'])
    @include('components.datatable.table')
  @endcomponent
@else
  @include('components.datatable.table')
@endif
