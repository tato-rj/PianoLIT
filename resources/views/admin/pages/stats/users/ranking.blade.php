<div class="col-12 p-3">
  <div class="border py-4 px-3">
    <div class="ml-2 mb-4">
      <h4 class="mb-1"><strong>Views</strong></h4>
      <p class="text-muted">Ranking of the number of favorites and views from each user.</p>
    </div>
    <div class="px-2">
      <table class="table table-hover" id="users-table">
        <thead>
          <tr>
            <th class="border-0" scope="col">Date</th>
            <th class="border-0" scope="col">Name</th>
            <th class="border-0" scope="col">Pieces viewed</th>
            <th class="border-0" scope="col">Pieces favorited</th>
            <th class="border-0" scope="col">Status</th>
            <th class="border-0" scope="col"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($users as $user)
          <tr>
            <td title="Signed up at {{$user->created_at->format('g:i:s a')}}">{{$user->created_at->toFormattedDateString()}}</td>
            <td>{{$user->full_name}}</td>
            <td>{{$user->views_count}}</td>
            <td>{{$user->favorites_count}}</td>
            <td>{{ucfirst($user->getStatus())}}</td>
            <td class="text-right">
              <a href="{{route('admin.users.show', $user->id)}}" class="text-muted"><i class="far fa-eye align-middle"></i></a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>