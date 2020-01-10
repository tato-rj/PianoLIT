<table class="table table-hover w-100 " id="{{$table}}-table">
  <thead class="invisible">
    <tr>
      @foreach($columns as $column)
      @if($column == 'checkbox')
      <th class="border-0" scope="col">
        <div class="custom-control custom-checkbox">
          <input type="checkbox" class="custom-control-input" id="check-all-datatable">
          <label class="custom-control-label" for="check-all-datatable"></label>
        </div>
      </th>      
      @else
      <th class="border-0" scope="col">{{$column}}</th>
      @endif
      @endforeach
    </tr>
  </thead>
  <tbody>
  </tbody>
</table>
<div class="datatable-loading text-center text-muted">Loading</div>