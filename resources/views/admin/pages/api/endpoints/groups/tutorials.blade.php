@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.users.tutorial-requests.show', ['user_id' => \App\User::has('tutorialRequests')->first()]),
'args' => ['user_id'], 
'title' => 'Requested tutorials'])

@include('admin.pages.api.endpoints.card', [
'depricated' => true,
'type' => 'GET', 
'route' => route('api.tutorial-requests', ['user_id' => \App\User::has('tutorialRequests')->first()]),
'args' => ['user_id'], 
'title' => 'Requested tutorials'])