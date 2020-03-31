@foreach($memberships as $membership)
  @if($loop->iteration <= $limit)
  <tr class="sortable">
    <td class="" style="width: 10%">
      <div class="text-truncate">{{$membership->user->full_name}}</div>
    </td>
    <td class=" pr-0" style="width: 80%; vertical-align: inherit;">
      <div class="text-{{$membership->renewal_color}}">
        <strong>{{$membership->renews_at->diffForHumans()}}</strong>
      </div>
    </td>
    <td class="text-right pl-0" style="width: 10%">
      <div class="">
        {{$membership->renews_at->toFormattedDateString()}}
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