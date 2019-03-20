@component('components/alerts/alert', ['alert' => 'success'])
	@slot('message')
		<strong class="mr-2">Success |  </strong>{{$message}}
	@endslot
@endcomponent