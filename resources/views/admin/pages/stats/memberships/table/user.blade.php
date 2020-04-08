@if($membership->user()->exists())
	<td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 5%">
	  <div class="text-truncate">{{$membership->user->id}}</div>
	</td>
	<td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 10%">
	  <div class="text-truncate">{{$membership->user->full_name}}</div>
	</td>
@else
	<td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 5%">
	  <div class="text-truncate text-muted">-</div>
	</td>
	<td class="{{$loop->last ? 'pt-1 pb-2 ' : 'py-1'}}" style="width: 10%">
	  <div class="text-truncate text-muted"><i>account deleted</i></div>
	</td>
@endif