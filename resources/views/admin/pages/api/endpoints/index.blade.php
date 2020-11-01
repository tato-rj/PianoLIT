@extends('admin.layouts.app')

@section('head')
<style type="text/css">
.mark, mark {
  padding: 0 !important;
}
</style>
@endsection

@section('content')

@php($colors = ['get' => 'blue', 'post' => 'green'])

<div class="content-wrapper">  
  <div class="container-fluid">
    <div class="row mb-4">
      <div class="col-12">
        <input type="text" name="search-endpoint" placeholder="Search here" class="form-control ">
      </div>
    </div>
    <div class="row" id="endpoints" style="display: none;">
      @include('admin.pages.api.endpoints.card', [
        'type' => 'GET', 
        'route' => route('api.discover', ['user_id' => \App\User::tester()->id]), 
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
        'route' => route('api.search', ['search' => 'melancholic']),
        'args' => ['search'],
        'title' => 'Search results'])

      @include('admin.pages.api.endpoints.card', [
        'type' => 'GET', 
        'route' => route('api.query-suggestions'),
        'args' => [],
        'title' => 'Query suggestions'])
      
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
        'route' => route('api.users.tutorial-requests.show', ['user_id' => \App\User::has('tutorialRequests')->first()->id]),
        'args' => ['user_id'], 
        'title' => 'Requested tutorials'])

      @include('admin.pages.api.endpoints.card', [
        'depricated' => true,
        'type' => 'GET', 
        'route' => route('api.tutorial-requests', ['user_id' => \App\User::has('tutorialRequests')->first()->id]),
        'args' => ['user_id'], 
        'title' => 'Requested tutorials'])

      @include('admin.pages.api.endpoints.card', [
        'type' => 'GET', 
        'route' => route('api.users.favorites.show', ['user_id' => \App\User::has('favorites')->first()->id]),
        'args' => ['user_id'], 
        'title' => 'User favorites'])
      
      @include('admin.pages.api.endpoints.card', [
        'depricated' => true,
        'type' => 'POST', 
        'route' => route('api.users.favorites.show', ['user_id' => \App\User::has('favorites')->first()->id]),
        'args' => ['user_id'], 
        'title' => 'User favorites'])

      @include('admin.pages.api.endpoints.card', [
        'type' => 'POST', 
        'route' => route('api.users.login', ['email' => \App\User::tester()->email, 'password' => 'tester']), 
        'args' => ['email', 'password'],
        'title' => 'User\'s login'])

      @include('admin.pages.api.endpoints.card', [
        'type' => 'GET', 
        'route' => route('api.pieces.find', ['search' => \App\Piece::first()->id, 'user_id' => 1]),
        'args' => ['search', 'user_id'], 
        'title' => 'Call for a single piece'])

      @include('admin.pages.api.endpoints.card', [
        'depricated' => true,
        'type' => 'POST', 
        'route' => route('api.pieces.find', ['search' => \App\Piece::first()->id, 'user_id' => 1]),
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
        'route' => route('api.users.favorites.update', ['user_id' => \App\User::tester()->id, 'piece_id' => \App\Piece::first()->id]), 
        'args' => ['user_id', 'piece_id'],
        'title' => 'Update user\'s favorites'])

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
        'route' => route('api.playlists.show', ['playlist' => \App\Playlist::first(), 'user_id' => \App\User::tester()->id]), 
        'args' => ['user_id'],
        'title' => 'Pieces from a playlist'])

      @include('admin.pages.api.endpoints.card', [
        'type' => 'POST', 
        'route' => route('api.pieces.increment-views', ['user_id' => \App\User::tester()->id, 'piece_id' => \App\Piece::inRandomOrder()->first()->id]), 
        'args' => ['user_id', 'piece_id'],
        'title' => 'Increment views for a piece'])

      @include('admin.pages.api.endpoints.card', [
        'type' => 'GET', 
        'route' => route('posts.app', \App\Blog\Post::inRandomOrder()->first()), 
        'args' => [],
        'title' => 'Show the blog post on the app'])
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/mark.js/8.11.1/jquery.mark.es6.min.js"></script>
<script type="text/javascript">
$('.endpoint-card').sort(function(a, b) {
  return a.textContent < b.textContent ? -1 : 1;
}).appendTo('#endpoints');
$('#endpoints').show();
</script>

<script type="text/javascript">
var marker = new Mark('div#endpoints');

$('input[name="search-endpoint"]').on('keyup', function() {
  let val = $(this).val().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  $('.endpoint-card').unmark();

  if (val.length > 2) {
    console.log('Find names with: '+val);
    $('.endpoint-card').each(function() {
      let $card = $(this);
      let text = this.textContent;

      if (text.includes(val)) {
        $card.show();
        $card.mark(val);
      } else {
        if ($card.has('mark').length == 0) {
          $card.hide();
        }
      }
    });
  } else {
    console.log('Show all');
    $('#endpoints .endpoint-card').show();
    $('.endpoint-card').unmark();
  }
});

</script>
<script type="text/javascript">
$('.endpoint-card form a').on('click', function(e) {
  e.preventDefault();
  $(this).closest('form').submit();
});
</script>
@endsection