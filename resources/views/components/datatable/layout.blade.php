@php($isStandardTable = $standard ?? false)
@if($isStandardTable)
  @component('admin.components.datatable-frame', ['tableId' => $table.'-table'])
    @include('components.datatable.table')
  @endcomponent
@else
  @include('components.datatable.table')
@endif
