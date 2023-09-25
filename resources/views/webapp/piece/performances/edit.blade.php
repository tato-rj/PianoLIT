<button class="btn-sm btn-primary btn w-100 mb-2 mt-2" data-toggle="modal" data-target="#insights-performance-{{$performance->id}}-modal">@fa(['icon' => 'chart-line'])Insights</button>

<button class="btn-sm btn-red-outline btn w-100" data-toggle="modal" data-target="#delete-performance-{{$performance->id}}-modal">@fa(['icon' => 'trash-alt'])Delete submission</button>

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

@component('components.modal', [
  'id' => 'insights-performance-'.$performance->id.'-modal',
  'header' => 'Insights'])
@slot('body')
<div class="text-center">
  <h1 class="text-teal">@fa(['icon' => 'hands-clapping', 'mr' => 0])</h1>
  <h5>{{$performance->claps_sum}} claps</h5>
</div>
@endslot
@endcomponent