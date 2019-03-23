<div class="tab-pane fade {{request('section') == 'manage' ? 'show active' : null}} m-3" id="manage">
  <div class="row">
    @if(in_array($user->getStatus(), ['trial', 'expired']))
    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <a href="" data-toggle="modal" data-target="#trial-modal" class="link-none">
        <div class="bg-pastel p-4 rounded">
          <p class="mb-2">
            <strong><i class="fas fa-calendar-alt mr-2"></i>{{$user->getStatus() == 'trial' ? 'Extend' : 'Restart'}} Trial</strong>
          </p>
          <span><small>Add +1 week of free trial</small></span>
        </div>
      </a>
    </div>
    @endif
    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <a href="mailto:{{$user->email}}" class="link-none">
        <div class="bg-intermediate p-4 rounded">
          <p class="mb-2">
            <strong><i class="fas fa-envelope mr-2"></i>Contact User</strong>
          </p>
          <span><small>Send an email to {{$user->email}}</small></span>
        </div>
      </a>
    </div>

    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <a href="" data-name="{{$user->full_name}}" data-url="{{route('admin.users.destroy', $user->id)}}" data-toggle="modal" data-target="#delete-modal" class="link-none">
        <div class="bg-advanced p-4 rounded">
          <p class="mb-2">
            <strong><i class="fas fa-trash-alt mr-2"></i>Delete account</strong>
          </p>
          <span><small>This action cannot be undone</small></span>
        </div>
      </a>
    </div>
  </div>
</div>