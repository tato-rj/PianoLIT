@include('admin.pages.api.endpoints.card', [
'type' => 'POST', 
'route' => route('api.users.login', ['email' => \App\User::tester()->email, 'password' => 'tester']), 
'args' => ['email', 'password'],
'title' => 'User\'s login'])