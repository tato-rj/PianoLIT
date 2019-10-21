@extends('layouts.app', [
	'title' => 'Your favorite composer\' birthday in your mailbox',
    'shareable' => [
        'keywords' => 'composer birthday,beethoven birthday,chopin birthday,mozart birthday, classical birthday',
        'title' => 'Your favorite composer\' birthday in your mailbox',
        'description' => 'We\'ll send you an email whenever a famous composer has a birthday, along with some relevant world news from the time the composer was born.',
        'thumbnail' => asset('images/misc/thumbnails/birthdays.jpg'),
        'created_at' => carbon('21-10-2019'),
        'updated_at' => carbon('21-10-2019')
	]])

@push('header')
<style type="text/css">
@media only screen and (min-width: 1000px) {
  #content {
    margin-top: -120px;
  }
}
</style>
@endpush

@section('content')

<div class="container mb-6">
	<div class="row">
		<div class="col-lg-7 col-md-6 col-12">
			<img src="{{asset('images/mockup/birthdays.jpg')}}" class="w-100">
		</div>
		<div class="col-lg-5 col-md-6 col-sm-8 col-10 mx-auto d-flex align-items-center">
			<div id="content">
				<h3 class="mb-4">Never miss your favorite composer's birthday!</h3>
				<p class="lead">We'll send you an email whenever a famous composer has a birthday, along with some relevant world news from the time the composer was born, you'll love it :)</p>
				<form method="POST" action="{{route('subscriptions.store')}}">
					@csrf
					<input type="hidden" name="subscription_name" placeholder="Your name here">
					<input type="hidden" name="started_at" value="{{now()}}">
					<div class="form-group">
						<input required type="email" name="email" placeholder="EMAIL ADDRESS" class="input-center form-control w-100 input-light">
					</div>
					@include('components/form/error', ['field' => 'email'])
					<button type="submit" class="btn btn-primary shadow btn-block mb-2">SUBSCRIBE</button>
					<div class="text-muted"><small>Ps: we'll never share your email with anyone</small></div>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript">

</script>
@endpush