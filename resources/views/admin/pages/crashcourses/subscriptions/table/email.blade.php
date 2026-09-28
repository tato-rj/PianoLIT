<div>
    @if($user = $item->user())
    <a href="{{route('admin.users.show', $user)}}" class="text-nowrap">
      @icon('user', ['mr' => 2]){{$item->email}}
    </a>
    @else
    {{$item->email}}
    @endif
</div>