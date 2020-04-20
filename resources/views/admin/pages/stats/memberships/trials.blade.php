@foreach($memberships as $membership)
  <tr>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 5%">
      <div class="text-truncate">{{$membership->user_id}}</div>
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
      <div class="d-flex d-apart alert-yellow rounded-left px-2" style="height: 24px; width: {{percentage($membership->created_at->diffInDays(now()), 7)}}%; border-right: 1px solid #918300;">
        <small class="align-text-bottom"><strong>{{$membership->created_at->format('D jS')}}</strong></small>
        <small class="align-text-bottom"><strong>day {{$membership->created_at->diffInDays(now())}}</strong></small>
      </div>
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}} pl-0" style="width: 10%">
      <div class="rounded-right px-2 alert-green text-nowrap" style="border-left: 1px solid #2d995b;">
        <small class="align-text-bottom"><strong>{{$membership->renews_at->format('D jS')}}</strong></small>
      </div>
    </td>
  </tr>
@endforeach