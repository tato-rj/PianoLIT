@component('components/alerts/alert', ['alert' => 'danger'])
	@slot('message')
		<strong class="mr-2">Sorry |  </strong>{{$message}}
	@endslot
@endcomponent