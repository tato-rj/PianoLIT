@extends('admin.layouts.app')

@section('head')
<style type="text/css">
.btn-nav {
  color: #5e7083;
  border: none;
  border-bottom: 2px solid transparent;
}
.btn-nav[selected] {
  color: #212529 !important;
  font-weight: bold;
  border-bottom: 2px solid #0055fe;
}
</style>
@endsection

@section('content')
<div class="content-wrapper">
  @include('admin.pages.api.tabs.nav')

  <div class="text-center">
    <form method="POST" action="{{route('redis.update', 'app.discover')}}" class="mb-2">
      @csrf
      <button class="btn btn-outline-success btn-sm"><i class="fas fa-sync-alt mr-1"></i>Refresh discover page</button>
    </form>
    <div class="badge badge-pill alert-grey">Will auto refresh in {{carbon(intval(str_replace('app.discover-', '', $key)))->addDay()->diffForHumans()}}</div>
  </div>

  <div class="row">
   <div class="col-lg-6 col-md-8 col-10 mx-auto mb-5">
    
      @foreach($collection as $playlist)
      @if($loop->first)
      <section class="container-fluid">
          <div class="row">
              <div class="col-12 mt-4">
                  <h6><strong>{{$playlist['title']}}</strong></h6>
              </div>
          </div>
          <div class="pb-2 w-100">
            <form class="w-100" name="piece_{{snake_case($playlist['content'][0]->id)}}_form" method="POST" action="{{$playlist['content'][0]->source}}" target="_blank">
              <input type="hidden" name="search" value="{{$playlist['content'][0]->id}}">
              <input type="hidden" name="global">
              <input type="hidden" name="api">
              <input type="hidden" name="discover">
              @include('admin.pages.api.tabs.discover.card', ['model' => $playlist['content'][0], 'width' => '100%', 'height' => '151px'])
            </form>
          </div>    
      </section>
      @else
        @if(! empty($playlist))
        @component('admin.components.swiper', ['title' => $playlist['title']])
          @foreach($playlist['content'] as $model)
            <form name="{{$model->type == 'piece' ? 'piece_'.snake_case($model->id) : snake_case($model->name)}}_form" method="{{$model->type == 'piece' ? 'POST' : 'GET'}}" action="{{$model->source}}" target="_blank">
              <input type="hidden" name="search" value="{{$model->type == 'piece' ? $model->id : $model->name}}">
              <input type="hidden" name="global">
              <input type="hidden" name="api">
              <input type="hidden" name="discover">
              @if($playlist['type'] == 'composer')
              @include('admin.pages.api.tabs.discover.composer')
              @else
              @include('admin.pages.api.tabs.discover.card')
              @endif
            </form>
          @endforeach
        @endcomponent
        @endif
      @endif
      @endforeach

    </div>
  </div>
</div>

@if(! empty($pieces))
@component('admin.components.modals.results')
  @include('admin.pages.search.results')
@endcomponent
@endif

@endsection

@section('scripts')
<script type="text/javascript">
if ($('#results-modal').length > 0)
    $('#results-modal').modal('show');
</script>
@endsection