@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.search', ['search' => 'melancholic']),
'args' => ['search'],
'title' => 'Search results'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.query-suggestions'),
'args' => [],
'title' => 'Query suggestions'])