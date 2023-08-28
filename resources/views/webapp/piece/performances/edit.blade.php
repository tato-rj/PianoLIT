<div class="d-apart px-2">
  <label class="mb-0 text-muted"><strong>{{$performance->claps_sum}}</strong> claps</label>

  <div class="d-flex align-items-center">
    <button class="btn-raw btn-lg text-red" data-toggle="modal" data-target="#delete-performance-{{$performance->id}}-modal">@fa(['icon' => 'trash-alt', 'mr' => 0])</button>
  </div>
</div>

@component('components.modal', [
  'id' => 'delete-performance-'.$performance->id.'-modal',
  'header' => 'Delete my performance'])
@slot('body')
  Are you sure you want to do this?
  <p class="text-danger"><small>This action cannot be undone</small></p>
  <form method="POST" action="{{route('webapp.users.performances.destroy', $performance)}}" disable-on-submit>
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-block btn-danger">Yes, I am sure</button>
  </form>
@endslot
@endcomponent