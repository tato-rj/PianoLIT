@component('components.modal', ['id' => 'share-modal'])
@slot('header')
Share with a friend!
@endslot

@slot('body')
<form method="POST" disable-on-submit action="{{route('webapp.pieces.share', $piece)}}">
	@csrf

	<p>I'd like to send the {{$piece->medium_name}} to</p>
	@input(['bag' => 'default', 'type' => 'email', 'name' => 'recipient_email', 'placeholder' => 'Which email should we send this to?', 'classes' => 'input-light'])
	<button type="submit" class="btn rounded-pill btn-block btn-default">Share</button>
</form>
@endslot

@endcomponent