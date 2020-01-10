<div class="text-truncate">
  <i class="text-muted {{$item->origin_icon}}" style="font-size: {{$item->origin == 'ios'? '130%' : null}}"></i>
  <small class="ml-1 text-muted">{{$item->origin == 'ios'? 'iOS' : ucfirst($item->origin)}}</small>
  <span class="position-absolute invisible">{{$item->origin}}</span>
</div>
