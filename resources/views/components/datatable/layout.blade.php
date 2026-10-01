<table class="table table-hover w-100 " id="{{$table}}-table">
  <thead class="invisible">
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
  </tbody>
</table>