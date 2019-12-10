      <div class="col-12 mb-2">
        <a href="{{route('admin.users.show', $user->id)}}" class="link-none">
          <div class="d-flex align-items-center bg-light text-muted px-3 py-2 badge-pill hover-shadow-light t-2">
            
            @include('admin.pages.users.status-icon.'.$user->getStatus())

            <div class="px-3" style="flex-grow: 2">
              <span>
                <strong>{{$user->full_name}}</strong> | <small><i>signed up on {{$user->created_at->toFormattedDateString()}}</i></small>
              </span>
            </div>

            <div>
              @if($user->membership()->exists())
                @if($user->membership->expired())
                <span class="text-muted"><i><small>validated {{$user->membership->validated_at->diffForHumans()}}</small></i></span>
                @else
                <div><i class="fas fa-credit-card"></i></div>
                @endif
              @endif
            </div>
          </div>
        </a>
      </div>