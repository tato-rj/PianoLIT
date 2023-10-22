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
  'size' => 'sm',
  'header' => 'Insights'])
@slot('body')
<div class="d-apart">
  <div class="text-left">
    <h6 class="mb-2">@fa(['icon' => 'calendar-day', 'color' => 'grey'])Uploaded on</h6>
    <h6 class="m-0">@fa(['icon' => 'hands-clapping', 'color' => 'grey'])Claps</h6>
  </div>
  <div class="text-right">
    <p class="mb-2">{{$performance->created_at->toFormattedDateString()}}</p>
    <p class="m-0">{{$performance->claps_sum}} {{str_plural('claps', $performance->claps_sum)}}</p>
  </div>


{{--   <div class="">
    <h1 class="text-grey">@fa(['icon' => 'calendar-day', 'mr' => 0])</h1>
    <h5>{{$performance->created_at->toFormattedDateString()}}</h5>
  </div>
  <div class="">
    <h1 class="text-orange">@fa(['icon' => 'hands-clapping', 'mr' => 0])</h1>
    <h5>{{$performance->claps_sum}} {{str_plural('claps', $performance->claps_sum)}}</h5>
  </div> --}}
</div>
@endslot
@endcomponent