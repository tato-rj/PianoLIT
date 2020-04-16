@if($message = session('status'))
@alert([
	'color' => 'green',
	'message' => '<strong class="mr-2">Success |  </strong>' . $message,
	'dismissible' => true,
	'floating' => 'top'])
@endif

@if($message = session('error') ?? $errors->first())
@alert([
	'color' => 'red',
	'message' => '<strong class="mr-2">Sorry |  </strong>' . $message,
	'dismissible' => true,
	'floating' => 'top'])
@endif