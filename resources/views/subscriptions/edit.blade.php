@extends('layouts.app', ['title' => 'PianoLIT Subscription'])

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')

@include('components.title', [
    'title' => 'Subscription', 
    'subtitle' => 'Update your subscription status'])

<div class="container mt-5 mb-6">
	<div class="row">
		<div class="col-lg-8 col-md-10 col-12 mx-auto panel panel-default">
			<p class="text-center mb-4">The email <strong style="font-size: 1.2em">{{$subscription->email}}</strong> is subscribed to:</p>
			<table class="table table-bordered mx-auto" style="max-width: 380px">
				<tbody>
	              @foreach(\App\Subscription::lists(request('list')) as $list)
					<tr>
						<td><h5 class="text-teal m-0"><strong>{{snake_str($list)}}</strong></h5></td>
						<td class="text-center align-middle">@include('admin.components.toggle.subscription', ['list' => $list])</td>
					</tr>
	              @endforeach
				</tbody>
			</table>
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
$('input.status-toggle').on('change', function() {
  let $input = $(this);

  $.ajax({
    url: $input.attr('data-url'),
    type: 'PATCH',
    success: function(res) {
      alert('Your update was successful!');
    },
    error: function(xhr,status,error) {
    	alert('Something went wrong: ' + error);
    }
  });
});
</script>
@endpush