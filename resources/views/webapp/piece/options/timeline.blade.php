@extends('webapp.layouts.app', ['title' => 'Timeline'])

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.options.header')

<h5 class="mb-3 text-center">Timeline</h5>

@include('webapp.piece.components.timeline')

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">
</script>
@endpush