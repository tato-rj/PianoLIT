<form method="POST" action="{{route('admin.tutorial-requests.simulate')}}" class="form-inline">
  @csrf
  <select required class="form-control mr-2" name="user_id">
    <option selected disabled>Select the user</option>
   
    @foreach(\App\User::take(5)->get() as $user)
      <option value="{{$user->id}}">{{$user->full_name}}</option>
    @endforeach
  </select>
  <select required class="form-control mr-2" name="piece_id">
    <option selected disabled>Select the piece</option>
   
    @foreach(\App\Piece::take(5)->get() as $piece)
      <option value="{{$piece->id}}">{{$piece->medium_name}}</option>
    @endforeach
  </select>
  
  <button type="submit" class="btn btn-default">Simulate request</button>
</form>