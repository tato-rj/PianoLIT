<div>
    @if($user = $item->user())
    <a href="{{route('admin.users.show', $user)}}" class="text-nowrap">
      <i class="fas fa-user mr-2"></i>{{$item->email}}
    </a>
    @else
    {{$item->email}}
    @endif
</div>