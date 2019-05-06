<form method="GET" action="{{\URL::current()}}" class="form-inline">

  <select class="form-control form-control-sm mr-2" name="composer" onchange="this.form.submit()">
      <option selected disabled>Composer</option>
      @foreach(\App\Composer::all() as $composer)
      <option value="{{$composer->id}}" {{(request('composer') == $composer->id) ? 'selected' : ''}}>{{ucwords($composer->name)}}</option>
      @endforeach
    </select>
<select class="form-control form-control-sm mr-2" name="level" onchange="this.form.submit()">
  <option selected disabled>Level</option>
      @foreach(\App\Tag::levels()->get() as $field)
      <option value="{{$field->name}}" {{(request('level') == $field->name) ? 'selected' : ''}}>{{ucwords($field->name)}}</option>
      @endforeach
  </select>

  <div class="btn-group btn-group-toggle mr-2" title="Show only pieces created by me">
    <label class="btn btn-light {{(request('creator_id') == auth()->guard('admin')->user()->id) ? 'active' : ''}}">
      <input type="checkbox" name="creator_id" autocomplete="off" value="{{auth()->guard('admin')->user()->id}}" onchange="this.form.submit()" {{(request('creator_id') == auth()->guard('admin')->user()->id) ? 'checked' : ''}}><i class="fas fa-user"></i>
    </label>
  </div>
  <div class="btn-group btn-group-toggle mr-2" title="Pieces missing itunes links">
    <label class="btn btn-light {{request('missing_itunes') ? 'active' : ''}}">
      <input type="checkbox" name="missing_itunes" autocomplete="off" onchange="this.form.submit()" {{request('missing_itunes') ? 'checked' : ''}}><i class="fab fa-itunes"></i></i>
    </label>
  </div>
  <div class="btn-group btn-group-toggle mr-2" title="Pieces missing youtube videos">
    <label class="btn btn-light {{request('missing_youtube') ? 'active' : ''}}">
      <input type="checkbox" name="missing_youtube" autocomplete="off" onchange="this.form.submit()" {{request('missing_youtube') ? 'checked' : ''}}><i class="fab fa-youtube"></i></i>
    </label>
  </div>
  <div class="btn-group btn-group-toggle mr-2" title="Pieces missing audio recordings">
    <label class="btn btn-light {{request('missing_audio') ? 'active' : ''}}">
      <input type="checkbox" name="missing_audio" autocomplete="off" onchange="this.form.submit()" {{request('missing_audio') ? 'checked' : ''}}><i class="fas fa-volume-off"></i></i>
    </label>
  </div>
  <div class="btn-group btn-group-toggle mr-2" title="Pieces missing the score">
    <label class="btn btn-light {{request('missing_score') ? 'active' : ''}}">
      <input type="checkbox" name="missing_score" autocomplete="off" onchange="this.form.submit()" {{request('missing_score') ? 'checked' : ''}}><i class="fas fa-file-alt"></i></i>
    </label>
  </div>

  @if(!empty($sortable))
  <div class="btn-group btn-group-toggle ml-2" data-toggle="buttons">
    <label class="btn btn-light {{(request('order') == 'asc') ? 'active' : ''}}">
      <input type="radio" name="order" autocomplete="off" value="asc" onchange="this.form.submit()" {{(request('order') == 'asc') ? 'checked' : ''}}><i class="fas fa-sort-alpha-down"></i>
    </label>
    <label class="btn btn-light {{(request('order') == 'desc') ? 'active' : ''}}">
      <input type="radio" name="order" autocomplete="off" value="desc" onchange="this.form.submit()" {{(request('order') == 'desc') ? 'checked' : ''}}><i class="fas fa-sort-alpha-up"></i>
    </label>
  </div>
  @endif

</form>
<div class="text-right">
  <a href="{{url()->current()}}" class="text-muted pull-right"><small>reset filters</small></a>
</div>