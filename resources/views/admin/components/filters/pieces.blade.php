<div class="d-flex">
  <button class="btn  btn-light mr-2 btn-sm" data-toggle="modal" data-target="#abrsm-modal"><strong>ABRSM</strong></button>
  <button class="btn  btn-light mr-2 btn-sm" data-toggle="modal" data-target="#rcm-modal"><strong>RCM</strong></button>
  <form method="GET" action="{{\URL::current()}}" class="form-inline">
    <div class="btn-group btn-group-toggle mr-2" title="Show only pieces created by me">
      <label class="btn btn-light {{(request('creator_id') == auth()->guard('admin')->user()->id) ? 'active' : ''}}">
        <input type="checkbox" name="creator_id" autocomplete="off" value="{{auth()->guard('admin')->user()->id}}" onchange="this.form.submit()" {{(request('creator_id') == auth()->guard('admin')->user()->id) ? 'checked' : ''}}><i class="fas fa-user"></i>
      </label>
    </div>
    <div class="btn-group btn-group-toggle mr-2" title="Pieces missing itunes links">
      <label class="btn btn-light {{request('itunes') ? 'active' : ''}}">
        <input type="checkbox" name="itunes" autocomplete="off" value="missing" onchange="this.form.submit()" {{request('itunes') ? 'checked' : ''}}><i class="fab fa-itunes"></i></i>
      </label>
    </div>
    <div class="btn-group btn-group-toggle mr-2" title="Pieces missing youtube videos">
      <label class="btn btn-light {{request('youtube') ? 'active' : ''}}">
        <input type="checkbox" name="youtube" autocomplete="off" value="missing" onchange="this.form.submit()" {{request('youtube') ? 'checked' : ''}}><i class="fab fa-youtube"></i></i>
      </label>
    </div>
    <div class="btn-group btn-group-toggle mr-2" title="Pieces missing audio recordings">
      <label class="btn btn-light {{request('audio_path') ? 'active' : ''}}">
        <input type="checkbox" name="audio_path" autocomplete="off" value="missing" onchange="this.form.submit()" {{request('audio_path') ? 'checked' : ''}}><i class="fas fa-volume-off"></i></i>
      </label>
    </div>
    <div class="btn-group btn-group-toggle" title="Pieces missing the score">
      <label class="btn btn-light {{request('score_path') ? 'active' : ''}}">
        <input type="checkbox" name="score_path" autocomplete="off" value="missing" onchange="this.form.submit()" {{request('score_path') ? 'checked' : ''}}><i class="fas fa-file-alt"></i></i>
      </label>
    </div>

  </form>
</div>
<div class="text-right">
  <a href="{{url()->current()}}" class="text-muted pull-right"><small>reset filters</small></a>
</div>