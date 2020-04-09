@foreach($memberships as $membership)
  @if($loop->iteration <= $limit)
  <tr class="sortable">
    <td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 5%">
      <div class="text-truncate">{{$membership->user->id}}</div>
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
      <div class="text-muted">
        <strong>{{$membership->renews_at->diffForHumans()}}</strong>
      </div>
    </td>
    <td class="text-right pl-0 {{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 10%">
      <div class="text-nowrap">
        {{$membership->renews_at->toFormattedDateString()}}
      </div>
    </td>
{{--     <td>
      <a href="{{}}" class="btn btn-sm btn-warning"><i class="fas fa-sync-alt mr-1"></i>Validate</a>
    </td> --}}
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