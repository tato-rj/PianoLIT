@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Emails lists',
    'description' => 'Manage the emails lists'])

    <div class="row">
      <div class="col-12 mb-4">
        <div class="mb-4">
        @include('admin.pages.subscriptions.lists.create')
        </div>
      </div>
    </div>
  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')

@endsection