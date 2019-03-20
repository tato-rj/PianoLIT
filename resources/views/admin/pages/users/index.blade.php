@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Users',
    'description' => 'View detailed information about the users'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <button type="button" class="btn btn-sm btn-default mr-3" data-toggle="modal" data-target="#add-modal">
            <i class="fas fa-plus mr-2"></i>Create a new user
          </button>
    
          <form method="GET" action="{{route('admin.memberships.validate.all')}}">
            @csrf
            <button class="btn btn-sm btn-success"><i class="fas fa-clipboard-check mr-2"></i>Validate all subscriptions</button>
          </form>

        </div>
        <div>
          @include('admin.components.filters', ['filters' => []])
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>Showing {{$users->count()}} of {{$users->total()}}</small></p>
      </div>
      @foreach($users as $user)
      <div class="col-12 mb-2">
        <a href="{{route('admin.users.show', $user->id)}}" class="link-none">
          <div class="d-flex align-items-center bg-light text-muted px-3 py-2 badge-pill hover-shadow-light t-2">
            
            @include('admin.users.status-icon/'.$user->getStatus())

            <div class="px-3" style="flex-grow: 2">
              <span>
                <strong>{{$user->full_name}}</strong> | <small><i>signed up on {{$user->created_at->toFormattedDateString()}}</i></small>
              </span>
            </div>

            <div>
              @if($user->subscription()->exists())
                @if($user->subscription->expired())
                <span class="text-muted"><i><small>validated {{$user->subscription->validated_at->diffForHumans()}}</small></i></span>
                @else
                <div><i class="fas fa-credit-card"></i></div>
                @endif
              @endif
            </div>
          </div>
        </a>
      </div>
      @endforeach
    </div>

    {{-- PAGINATION --}}
    <div class="row mb-3">
          <div class="d-flex align-items-center w-100 justify-content-center my-4">
        {{ $users->links() }}    
        </div>
    </div>

  </div>
</div>

@endsection

@section('scripts')

@endsection