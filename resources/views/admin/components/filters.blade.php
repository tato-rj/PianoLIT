<form method="GET" action="{{\URL::current()}}" class="form-inline">
  @foreach($filters as $filter)
  <select class="form-control form-control-sm mr-2" name="{{str_singular($filter)}}" onchange="this.form.submit()">
    <option selected disabled>{{ucfirst(str_singular($filter))}}</option>
    @if($filter == 'composers')
      @foreach(\App\Composer::all() as $composer)
      <option value="{{$composer->id}}" {{(request('composer') == $composer->id) ? 'selected' : ''}}>{{ucwords($composer->name)}}</option>
      @endforeach
    @else
      @foreach(\App\Tag::$filter()->get() as $field)
      <option value="{{$field->name}}" {{(request(str_singular($filter)) == $field->name) ? 'selected' : ''}}>{{ucwords($field->name)}}</option>
      @endforeach
    @endif
  </select>
  @endforeach
  <div class="btn-group btn-group-toggle mr-2" title="Show only pieces created by me">
    <label class="btn btn-light {{(request('creator_id') == auth()->guard('admin')->user()->id) ? 'active' : ''}}">
      <input type="checkbox" name="creator_id" autocomplete="off" value="{{auth()->guard('admin')->user()->id}}" onchange="this.form.submit()" {{(request('creator_id') == auth()->guard('admin')->user()->id) ? 'checked' : ''}}><i class="fas fa-user"></i>
    </label>
  </div>
  <div class="btn-group btn-group-toggle" data-toggle="buttons">
    <label class="btn btn-light {{(request('order') == 'asc') ? 'active' : ''}}">
      <input type="radio" name="order" autocomplete="off" value="asc" onchange="this.form.submit()" {{(request('order') == 'asc') ? 'checked' : ''}}><i class="fas fa-sort-alpha-down"></i>
    </label>
    <label class="btn btn-light {{(request('order') == 'desc') ? 'active' : ''}}">
      <input type="radio" name="order" autocomplete="off" value="desc" onchange="this.form.submit()" {{(request('order') == 'desc') ? 'checked' : ''}}><i class="fas fa-sort-alpha-up"></i>
    </label>
  </div>
</form>
<a href="{{url()->current()}}" class="text-muted pull-right"><small>reset filters</small></a>