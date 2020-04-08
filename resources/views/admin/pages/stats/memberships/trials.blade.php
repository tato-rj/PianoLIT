@foreach($memberships as $membership)
  @if($loop->iteration <= $limit)
  <tr>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 5%">
      <div class="text-truncate">{{$membership->user_id}}</div>
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 10%">
      @if($membership->user()->exists())
      <div class="text-truncate">{{$membership->user->full_name}}</div>
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
  @else
    @if(!empty($more))
    <tr>
      <td colspan="3" class="p-0">
        <button data-url="" class="btn border-0 btn-light rounded-0 btn-block load-more">
          LOAD MORE
        </button>
      </td>
    </tr>
    @endif
    @break
  @endif
@endforeach