@extends('webapp.layouts.app', ['title' => $composer->name])


@push('header')
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
@endpush

@section('content')
@include('webapp.layouts.header')

<section>
	@include('webapp.composers.profile')
</section>
@endsection

@push('scripts')
<script type="text/javascript">
</script>
@endpush