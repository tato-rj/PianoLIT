@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.users.should-review', ['user_id' => 1]),
'args' => ['user_id'], 
'title' => 'Should we ask the user for a review'])