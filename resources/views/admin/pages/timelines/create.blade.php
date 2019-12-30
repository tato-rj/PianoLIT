<div class="w-100">
  <form method="POST" action="{{route('admin.timelines.store')}}">
    @csrf
    <div class="form-row mb-2">
      <div class="col-2 input-group-vertical">
        <div class="input-group">
          <input type="number" required name="year" placeholder="Year" min="1600" max="{{now()->year}}" class="form-control">
        </div>
        <div class="input-group">
          <select name="type" class="form-control">
            <option selected disabled>Type</option>
            @include('admin.pages.timelines.types')
          </select>
        </div>
      </div>
      <div class="col-10 input-group-vertical">
        <div class="input-group">
          <input type="text" required name="event" placeholder="New event here..." class="form-control">
        </div>
        <div class="input-group">
          <input type="text" required name="url" placeholder="Url address" class="form-control">
        </div>
      </div>
    </div>
    <div class="form-group text-right">
      <button type="submit" class="btn btn-default" style="white-space: nowrap;">Create event</button>
    </div>
  </form>
  @include('admin.components.feedback', ['field' => 'year'])
  @include('admin.components.feedback', ['field' => 'event'])
</div>