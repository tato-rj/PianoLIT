@extends('layouts.app', ['title' => 'My downloads'])

@section('content')
<div class="container download-library py-4 py-md-5" data-download-library>
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            @include('users.components.library-header', ['heading' => 'My downloads', 'icon' => 'download'])
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
