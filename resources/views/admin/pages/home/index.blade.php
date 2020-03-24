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
        <div class="col-lg-8 col-md-6 col-12 row no-gutters"> 
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

        <div class="col-lg-4 col-md-6 col-12">
          <div class="border rounded">
            <div>
              <img src="{{$freepick->cover_image()}}" class="w-100 rounded-top">
            </div>
            <div class="px-3 pt-2 pb-3 mb-2 border-bottom">
              <p class="text-blue mb-1"><small>THIS WEEK'S FREEPICK</small></p>
              <h5 class="mb-0"><strong>{{$freepick->medium_name}}</strong></h5>
              <p class="mb-0">by {{$freepick->composer->short_name}}</p>
            </div>
            <div class="px-3 py-2 mb-2">
              <div class="d-flex align-items-end mb-2">
                <h3 class="mr-2 mb-0"><i class="fas fa-eye mr-2 text-blue"></i>{{$freepick->views_count}}</h3>
                <p class="mb-0">views</p>
              </div>
              <div class="d-flex align-items-end">
                <h3 class="mr-2 mb-0"><i class="fas fa-heart mr-2 text-red"></i>{{$freepick->favorites_count}}</h3>
                <p class="mb-0">favorites</p>
              </div>
            </div>
          </div>
        </div>
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