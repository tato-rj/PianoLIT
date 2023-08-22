@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper">
<div class="container">
	<form method="POST" action="{{route('admin.api.test-upload')}}" enctype="multipart/form-data">
		@csrf
		@file(['bag' => 'default', 'name' => 'video', 'placeholder' => 'Choose video'])
		@submit(['label' => 'Upload video', 'block' => true])
	</form>
</div>
</div>
@endsection

@section('scripts')

<script type="text/javascript">

</script>
@endsection
