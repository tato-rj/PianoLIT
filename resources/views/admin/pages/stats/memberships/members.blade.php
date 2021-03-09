@foreach($memberships as $membership)
  <tr>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 5%">
      <div class="text-truncate">{{$membership->user()->exists() ? $membership->user->id : null}}</div>
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 10%">
      @if($membership->user()->exists())
      <div class="text-truncate"><a href="{{route('admin.users.show', $membership->user)}}" class="link-blue">{{$membership->user->full_name}}</a></div>
      @else
      <div class="text-truncate text-muted"><i>account deleted</i></div>
      @endif
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 10%">
      <div class="text-truncate">{{$membership->source->plan_name}}</div>
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}} pr-0" style="width: 65%; vertical-align: inherit;">
      <div class="text-{{$membership->source->renewal_color}}">
        <strong>{{$membership->source->renews_at->diffForHumans()}}</strong>
      </div>
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}} text-right pl-0" style="width: 10%">
      <div class="text-nowrap">
        {{$membership->source->renews_at->toFormattedDateString()}}
      </div>
    </td>
  </tr>
@endforeach