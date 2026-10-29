@extends('layouts.app', ['title' => 'My downloads'])

@section('content')
<div class="container py-4 py-md-5">
    <div class="row">
        <div class="col-12">
            <header class="mb-4 mb-md-5">
                <h1 class="mb-2">My downloads</h1>
                <p class="text-muted mb-0">Your eBooks, eScores and infographics, ready to download again.</p>
                <a class="d-inline-block mt-3" href="{{ route('users.orders') }}">Track your merchandise orders @icon('arrow-right')</a>
            </header>
            @if($purchases->total())
                @if($purchases->isNotEmpty())
                    @paginationCount(['collection' => $purchases, 'align' => 'left', 'item' => 'resource'])
                    <div class="profile-panel p-0 overflow-hidden mb-4">
                        @include('users.purchases.list')
                    </div>
                @else
                    <p class="profile-panel">No resources on this page. <a href="{{ route('users.purchases') }}">Return to your library</a></p>
                @endif
                @pagination(['collection' => $purchases])
            @else
                @include('users.purchases.empty')
            @endif
        </div>
    </div>
</div>
@endsection
