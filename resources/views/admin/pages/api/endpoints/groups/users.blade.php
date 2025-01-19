@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.users.should-review', ['user_id' => \App\User::latest()->first()->id]),
'args' => ['user_id'], 
'title' => 'Should we ask the user for a review'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.users.suggestions', ['user_id' => \App\User::latest()->first()->id]),
'args' => ['user_id'], 
'title' => 'Get suggested pieces'])