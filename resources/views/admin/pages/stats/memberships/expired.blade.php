@foreach($memberships as $membership)
  <tr>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 5%">
      <div class="text-truncate">{{$membership->user->id}}</div>
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 10%">
      @if($membership->user()->exists())
      <div class="text-truncate"><a href="{{route('admin.users.show', $membership->user)}}" class="link-blue">{{$membership->user->full_name}}</a></div>
      @else
      <div class="text-truncate text-muted"><i>account deleted</i></div>
      @endif
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 10%">
      <div class="text-truncate">{{$membership->plan_name}}</div>
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}} pr-0" style="width: 65%; vertical-align: inherit;">
      <div class="text-muted">
        <strong>{{$membership->renews_at->diffForHumans()}}</strong>
      </div>
    </td>
    <td class="text-right pl-0 {{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 10%">
      <div class="text-nowrap">
        {{$membership->renews_at->toFormattedDateString()}}
      </div>
    </td>
  </tr>
@endforeach