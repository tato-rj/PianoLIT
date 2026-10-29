@extends('layouts.app', ['title' => 'My downloads'])

@section('content')
<div class="container download-library py-4 py-md-5" data-download-library>
    <div class="row">
        <div class="col-12">
            <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 mb-md-5">
                <h1 class="h2 mb-0">My downloads</h1>
                <a href="{{ route('users.orders') }}">Track your orders @icon('arrow-right')</a>
            </header>
            @if($purchases->total() || $filters['q'] !== '' || $filters['type'] !== '')
                @include('users.purchases.toolbar')
                @if($purchases->isNotEmpty())
                    @include('users.purchases.list')
                @elseif($purchases->total())
                    <p class="py-5 text-center">No resources on this page. <a href="{{ route('users.purchases', array_filter($filters)) }}">Return to your library</a></p>
                @else
                    <div class="text-center py-5">
                        <h2 class="h4 mb-3">No downloads found</h2>
                        <a class="btn btn-secondary" href="{{ route('users.purchases') }}">Show all downloads</a>
                    </div>
                @endif
                @if($purchases->hasPages()) @pagination(['collection' => $purchases]) @endif
            @else
                @include('users.purchases.empty')
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ mix('js/views/downloads.js') }}" defer></script>
@endpush
