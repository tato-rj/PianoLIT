<div class="text-right">
  @component('components.datatable.actions', ['actions' => []])
	
	@if(! $item->isCompleted && ! $item->isCancelled)
    <a href="#" 
      data-url="{{route('admin.crashcourses.subscriptions.resend', $item)}}" 
    data-action="resend the last lesson to {{$item->email}}"
      title="Resend last lesson" data-toggle="modal" data-target="#confirm-modal" class="text-nowrap btn btn-sm btn-outline-secondary mr-2">
      <i class="fas fa-redo-alt mr-2"></i>Resend
    </a>

  	<a href="#" 
  		data-url="{{route('admin.crashcourses.subscriptions.next', $item)}}" 
		data-action="send the next lesson to {{$item->email}}"
  		title="Send next lesson" data-toggle="modal" data-target="#confirm-modal" class="text-nowrap btn btn-sm btn-outline-secondary mr-2">
  		<i class="fas fa-forward mr-2"></i>Send next
  	</a>

  	<a href="#" 
  		data-url="{{route('admin.crashcourses.subscriptions.cancel', $item)}}" 
		data-action="stop {{$item->first_name}}'s subscription"
  		title="Stop subscription" data-toggle="modal" data-target="#confirm-modal" class="text-nowrap btn btn-sm btn-danger">
  		<i class="fas fa-stop-circle mr-2"></i>Stop
  	</a>
  	@endif

    @if($user = $item->user())
    <a href="{{route('admin.users.show', $user)}}" class="text-nowrap btn btn-sm btn-green ml-2 pl-2 border-left">
      <i class="fas fa-user mr-2"></i>User
    </a>
    @endif

  @endcomponent
</div>
