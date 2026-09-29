@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'gear',
      'title' => 'Settings', 
      'subtitle' => '',
    ])


  </div>
</div>


@endsection

@section('scripts')
@endsection