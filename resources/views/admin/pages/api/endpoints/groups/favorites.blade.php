@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.users.favorites.show', ['user_id' => \App\User::has('favorites')->first()]),
'args' => ['user_id'], 
'title' => 'Get user\'s favorites'])

@include('admin.pages.api.endpoints.card', [
'depricated' => true,
'type' => 'POST', 
'route' => route('api.users.favorites.show', ['user_id' => \App\User::has('favorites')->first()]),
'args' => ['user_id'], 
'title' => 'Get user\'s favorites'])

@include('admin.pages.api.endpoints.card', [
'type' => 'POST', 
'route' => route('api.users.favorites.update', ['user_id' => \App\User::tester(), 'piece_id' => \App\Piece::first()]), 
'args' => ['user_id', 'piece_id'],
'title' => 'Update user\'s favorites'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.users.favorites.folders.index', ['user_id' => \App\User::tester()]), 
'args' => ['user_id'],
'title' => 'Show user\'s favorite folders'])
