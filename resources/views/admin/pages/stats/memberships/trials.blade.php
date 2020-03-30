@foreach($memberships as $membership)
  @if($loop->iteration <= $limit)
  <tr class="sortable">
    <td style="width: 10%">{{truncate($membership->user->full_name, 10)}}</td>
    <td class="text-nowrap d-flex" style="width: 100%">
      <div class="flex-grow-1">
        <div class="h-100 alert-yellow rounded-left px-2" style="width: {{percentage($membership->created_at->diffInDays($membership->renews_at), 7)}}%; border-right: 2px solid #918300;">
          <small class="align-text-bottom"><strong>{{$membership->created_at->format('D jS')}}</strong></small>
        </div>
      </div>
      <div>
        <div class="rounded-right px-2 alert-green" style="border-left: 2px solid #2d995b;">
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