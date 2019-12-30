<table class="table table-hover w-100" id="{{$model}}-table">
  <thead>
    <tr>
      @foreach($columns as $column)
      <th class="border-0" scope="col">{{$column}}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach(eval("return $$model;") as $item)
    @include(empty($rows) ? "admin.pages.$model.row" : $rows)
    @endforeach
  </tbody>
</table>