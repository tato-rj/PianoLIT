<form method="POST" action="{{route('admin.subscriptions.lists.store')}}" class="form-row">
  @csrf
  <div class="col-lg-3 col-md-4 col-12">
    <div class="form-group">
      <input type="text" class="form-control" name="name" required value="{{old('name')}}">
    </div>
    <button type="submit" class="btn btn-default btn-block">Subscribe</button>
  </div>
  <div class="col-lg-9 col-md-8 col-12">
    <div class="form-group h-100">
      <textarea class="form-control h-100" name="emails" required placeholder="Describe the list here">{{old('description')}}</textarea>
    </div>
  </div>
</form>