<form method="POST" action="{{route('admin.subscriptions.store')}}" class="form-row">
  @csrf
  <div class="col-lg-9 col-md-8 col-12">
    <div class="form-group h-100">
      <textarea class="form-control h-100" name="emails" required placeholder="Separate the emails with comma and a space, like this: email1@example.com, email2@example.com, email3@example.com, etc...">{{old('emails')}}</textarea>
    </div>
  </div>
  <div class="col-lg-3 col-md-4 col-12">
    <div class="form-group">
      <input class="form-control" type="text" name="origin_url" value="{{old('origin_url')}}" required placeholder="Where are these emails from?">
    </div>
    <button type="submit" class="btn btn-default btn-block">Subscribe</button>
  </div>
</form>