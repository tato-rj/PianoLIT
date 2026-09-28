<span class="text-truncate {{$item->email_confirmed ? 'text-blue' : 'text-muted'}}" title="{{$item->email_confirmed ? 'Confirmed email on ' . $item->email_verified_at->toFormattedDateString() : 'Unconfirmed email'}}">
  @icon($item->origin_icon, ['mr' => 0, 'styles' => 'font-size: ' . ($item->origin == 'ios' ? '130%' : null)])
  <small class="ml-1">{{$item->formatted_origin}}</small>
</span>
