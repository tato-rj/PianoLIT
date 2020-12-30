@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.discover', ['user_id' => \App\User::tester()]), 
'args' => ['user_id'],
'title' => 'Discover tab'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.composers'), 
'args' => [],
'title' => 'View all composers'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.tour', ['search' => 'intermediate melancholic dreamy']),
'args' => ['search'], 
'title' => 'Tour results'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.tags'), 
'args' => [],
'title' => 'Tags tab'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.explore'), 
'args' => [],
'title' => 'Explore tab'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.playlists.index', ['group' => 'journey']), 
'args' => [],
'title' => 'View "Follow a Path" playlist'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.playlists.index'), 
'args' => [],
'title' => 'View general playlists'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('api.playlists.show', ['playlist' => \App\Playlist::first(), 'user_id' => \App\User::tester()]), 
'args' => ['user_id'],
'title' => 'Pieces from a playlist'])

@include('admin.pages.api.endpoints.card', [
'type' => 'GET', 
'route' => route('posts.app', \App\Blog\Post::inRandomOrder()->first()), 
'args' => [],
'title' => 'Show the blog post on the app'])