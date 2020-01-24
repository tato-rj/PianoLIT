<form method="POST" action="{{route('admin.subscriptions.lists.store')}}" class="form-row" disable-on-submit>
  @csrf
  <div class="col-lg-3 col-md-6 col-12">
    <div class="form-group">
      <input type="text" class="form-control" name="name" placeholder="List name" required value="{{old('name')}}">
    </div>
    <button type="submit" class="btn btn-default btn-block">Create list</button>
  </div>
  <div class="col-lg-9 col-md-6 col-12">
    <div class="form-group h-100">
      <textarea class="form-control h-100" name="description" required placeholder="Describe the list here">{{old('description')}}</textarea>
    </div>
  </div>
</form>