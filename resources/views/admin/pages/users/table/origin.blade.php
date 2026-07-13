<span class="text-truncate {{$item->email_confirmed ? 'text-blue' : 'text-muted'}}" title="{{$item->email_confirmed ? 'Confirmed email on ' . $item->email_verified_at->toFormattedDateString() : 'Unconfirmed email'}}">
  <i class="{{$item->origin_icon}}" style="font-size: {{$item->origin == 'ios' ? '130%' : null}}"></i>
  <small class="ml-1">{{$item->formatted_origin}}</small>
</span>
