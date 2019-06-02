@extends('layouts.app')

@push('header')
<style type="text/css">
#circle {
	border: 6px solid #f8f9fa;
	border-radius: 50%;
	width: 420px;
	height: 420px;
	position: relative;
}

.key-letter {
    color: lightgrey;
    background: #e7ebee; 
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 2.5em;
    font-weight: bold;
    position: absolute;
    top: -40px;
    left: 160px;
}

.key-letter:hover {
	color: #1876f6;
	background: #e7ebee;
}

.key-letter:nth-of-type(1) {
	
}
</style>
@endpush

@section('content')
<div class="container">
	<div class="row h-100vh">
		<div class="col-6 p-5">
			<div id="circle">
				<div class="key-letter t-2 cursor-pointer">C</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')

@endpush