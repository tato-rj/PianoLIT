<span title="{{$topUser ? $user->first_name.' is one of our biggest fans!' : null}}">
  {{$user->full_name}}
  {!! $topUser ? \App\Support\Icon::render('trophy', ['mr' => 0, 'classes' => 'ml-2 text-success']) : null !!}
  {!! $user->location ? $user->location->countryFlag : null !!}
</span>
