<button class="btn-sm btn-primary btn w-100 mb-2 mt-2" data-bs-toggle="modal" data-bs-target="#insights-performance-{{$performance->id}}-modal">@icon('chart-line')Insights</button>

<button class="btn-sm btn-red-outline btn w-100" data-bs-toggle="modal" data-bs-target="#delete-performance-{{$performance->id}}-modal">@icon('trash-2')Delete submission</button>

@component('components.modal', [
  'id' => 'delete-performance-'.$performance->id.'-modal',
  'header' => 'Delete my performance'])
@slot('body')
  Are you sure you want to do this?
  <p class="text-danger"><small>This action cannot be undone</small></p>
  <form method="POST" action="{{route('webapp.users.performances.destroy', $performance)}}" disable-on-submit>
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm d-block w-100 btn-danger">Yes, I am sure</button>
  </form>
@endslot
@endcomponent

@component('components.modal', [
  'id' => 'insights-performance-'.$performance->id.'-modal',
  'size' => 'sm',
  'header' => 'Insights'])
@slot('body')
<div class="d-apart">
  <div class="text-start">
    <h6 class="mb-2">@icon('calendar-days', ['color' => 'grey'])Uploaded on</h6>
    <h6 class="m-0">@icon('hand-heart', ['color' => 'grey'])Claps</h6>
  </div>
  <div class="text-end">
    <p class="mb-2">{{$performance->created_at->toFormattedDateString()}}</p>
    <p class="m-0">{{$performance->claps_sum}} {{str_plural('claps', $performance->claps_sum)}}</p>
  </div>


{{--   <div class="">
    <h1 class="text-grey">@icon('calendar-days', ['mr' => 0])</h1>
    <h5>{{$performance->created_at->toFormattedDateString()}}</h5>
  </div>
  <div class="">
    <h1 class="text-orange">@icon('hand-heart', ['mr' => 0])</h1>
    <h5>{{$performance->claps_sum}} {{str_plural('claps', $performance->claps_sum)}}</h5>
  </div> --}}
</div>
@endslot
@endcomponent