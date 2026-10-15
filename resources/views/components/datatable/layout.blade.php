@php($isStandardTable = $standard ?? false)
<table class="table {{ $isStandardTable ? 'align-middle' : 'table-hover' }} w-100" id="{{$table}}-table">
  <thead @unless($ready ?? false) class="invisible" @endunless>
    <tr>
      @foreach($columns as $column)
      @if($column == 'checkbox')
      <th @unless($isStandardTable) class="border-0" @endunless scope="col">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="check-all-datatable" @if($isStandardTable) aria-label="Select all rows on this page" @endif>
          <label class="form-check-label" for="check-all-datatable"></label>
        </div>
      </th>
      @else
      <th @unless($isStandardTable) class="border-0" @endunless scope="col">@if($isStandardTable && $column === '')<span class="visually-hidden">Actions</span>@else{{$column}}@endif</th>
      @endif
      @endforeach
    </tr>
  </thead>
  <tbody>
    {!! $initialBody ?? '' !!}
  </tbody>
</table>
