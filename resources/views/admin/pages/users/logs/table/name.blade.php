<span title="{{$topUser ? $user->first_name.' is one of our biggest fans!' : null}}">
  {{$user->full_name}}
  {!! $topUser ? '<i class="fas fa-trophy ml-2 text-success"></i>' : null !!}
  {!! $user->location ? $user->location->countryFlag : null !!}
</span>
