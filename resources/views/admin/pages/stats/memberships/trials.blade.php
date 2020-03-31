@foreach($memberships as $membership)
  @if($loop->iteration <= $limit)
  <tr class="sortable">
    {{-- <td style="width: 14%">{{truncate($membership->user->full_name, 12)}}</td> --}}
    <td class="py-1" style="width: 4%">
      <div class="text-truncate">{{$membership->user->full_name}}</div>
    </td>
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'pt-1'}} text-nowrap d-flex" style="width: 100%">
      <div style="width: 90%" class="flex-grow-1">
        <div class="h-100 d-flex d-apart alert-yellow rounded-left px-2" style="width: {{percentage($membership->created_at->diffInDays(now()), 7)}}%; border-right: 1px solid #918300;">
          <small class="align-text-bottom"><strong>{{$membership->created_at->format('D jS')}}</strong></small>
          <small class="align-text-bottom"><strong>day {{$membership->created_at->diffInDays(now())}}</strong></small>
        </div>
      </div>
      <div style="width: 10%">
        <div class="rounded-right px-2 alert-green" style="border-left: 1px solid #2d995b;">
          <small class="align-text-bottom"><strong>{{$membership->renews_at->format('D jS')}}</strong></small>
        </div>
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