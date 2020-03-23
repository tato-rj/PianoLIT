@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

  <div class="content-wrapper">
    <div class="container-fluid mb-3">
      
      <div>
        @foreach($birthdays as $composer)
        @include('admin.pages.home.alerts.birthday')
        @endforeach
        @foreach($deathdays as $composer)
        @include('admin.pages.home.alerts.deathday')
        @endforeach
      </div>

      @manager
      <!-- Icon Cards-->
      <div class="row mb-3">
        @include('admin.pages.home.card', [
          'color' => 'advanced',
          'icon' => 'music',
          'label' => $pieces_count . ' Pieces',
          'url' => route('admin.pieces.index')])

        @include('admin.pages.home.card', [
          'color' => 'beginner',
          'icon' => 'address-card',
          'label' => $composers_count . ' Composers',
          'url' => route('admin.composers.index')])

        @include('admin.pages.home.card', [
          'color' => 'elementary',
          'icon' => 'book-open',
          'label' => $quiz_results_count . ' Quiz results',
          'url' => route('admin.quizzes.index')])

        @include('admin.pages.home.card', [
          'color' => 'intermediate',
          'icon' => 'users',
          'label' => $users_count . ' Users',
          'url' => route('admin.users.logs')])

        @include('admin.pages.home.card', [
          'color' => 'elementary',
          'icon' => 'at',
          'label' => $subscriptions_count . ' Subscribers',
          'url' => route('admin.subscriptions.index')])

        @include('admin.pages.home.card', [
          'color' => 'advanced',
          'icon' => 'newspaper',
          'label' => $blog_count . ' Blog posts',
          'url' => route('admin.posts.index')])
      
      </div>
    
      @else
      <div class="row p-4">
        <div class="col-12 mb-4">
          <p>Welcome <strong>{{auth()->user()->name}}</strong>!</p>
          <p>So far you have created 
            {{auth()->user()->pieces_count}} {{str_plural('piece', auth()->user()->pieces_count) }} and 
          {{auth()->user()->composers_count}} {{str_plural('composer', auth()->user()->composers_count) }}. <a href="">Click here</a> to see how your pieces are doing in the app.</p>
        <p>Thank you for your contribution <i class="fas fa-smile text-warning"></i></p>
        </div>
        
      </div>
      @endmanager
    </div>
@endsection

@section('scripts')
<script type="text/javascript">

</script>
@endsection