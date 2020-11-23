@extends('webapp.layouts.app')

@push('header')
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.options.header')

<section>
	@include('webapp.composers.show', ['composer' => $piece->composer])
</section>

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">

</script>
@endpush