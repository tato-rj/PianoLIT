@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.pieces.find', ['search' => \App\Piece::find(334), 'user_id' => 1]),
'args' => ['search', 'user_id'], 
'title' => 'Call for a single piece'])

@include('admin.pages.api.endpoints.card', [
'depricated' => true,
'type' => 'POST', 
'route' => route('api.pieces.find', ['search' => \App\Piece::first(), 'user_id' => 1]),
'args' => ['search', 'user_id'], 
'title' => 'Call for a single piece'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.pieces.collection', ['piece' => \App\Piece::first(), 'user_id' => 1]),
'args' => ['user_id'], 
'title' => 'Collection a piece belongs to'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.pieces.similar', ['piece' => \App\Piece::first(), 'user_id' => 1]),
'args' => ['user_id'],
'title' => 'More like this'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.pieces.timeline', ['piece' => \App\Piece::first()]), 
'args' => [],
'title' => 'Timeline for a piece'])

@include('admin.pages.api.endpoints.card', [
'type' => 'POST', 
'route' => route('api.pieces.increment-views', ['user_id' => \App\User::tester(), 'piece_id' => \App\Piece::inRandomOrder()->first()]), 
'args' => ['user_id', 'piece_id'],
'title' => 'Increment views for a piece'])