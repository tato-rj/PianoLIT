<div class="modal fade" id="edit-favorites" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit {{$user->first_name}}'s favorites</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="mx-2">
          @foreach($pieces as $piece)
          <div class="form-check my-2 align-items-center piece" data-url="{{route('api.users.favorites.update')}}" data-id="{{$piece->id}}" data-user_id={{$user->id}}>
            <i class="{{$user->favorites()->find($piece->id) ? 'fas' : 'far'}} fa-heart mr-1 text-danger"></i>
            <span class="cursor-pointer">{{$piece->long_name}}</span>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>