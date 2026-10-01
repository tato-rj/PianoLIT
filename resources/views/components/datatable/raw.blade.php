<table class="table table-hover w-100" id="{{$model}}-table">
  <thead>
    <tr>
      @foreach($columns as $column)
      @if($column == 'checkbox')
      <th class="border-0" scope="col">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="check-all-datatable">
          <label class="form-check-label" for="check-all-datatable"></label>
        </div>
      </th>
      @else
      <th class="border-0" scope="col">{{$column}}</th>
      @endif
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach(eval("return $$model;") as $item)
    @include(empty($rows) ? "admin.pages.$model.row" : $rows)
    @endforeach
  </tbody>
</table>