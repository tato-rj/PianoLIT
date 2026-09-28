<div class="text-right">
  @component('components.datatable.actions', ['actions' => []])
	
	@if(! $item->isCompleted && ! $item->isCancelled)
    <a href="#" 
      data-url="{{route('admin.crashcourses.subscriptions.resend', $item)}}" 
    data-action="resend the last lesson to {{$item->email}}"
      title="Resend last lesson" data-toggle="modal" data-target="#confirm-modal" class="text-nowrap btn btn-sm btn-outline-secondary mr-2">
      @icon('rotate-cw', ['mr' => 2])Resend
    </a>

  	<a href="#" 
  		data-url="{{route('admin.crashcourses.subscriptions.next', $item)}}" 
		data-action="send the next lesson to {{$item->email}}"
  		title="Send next lesson" data-toggle="modal" data-target="#confirm-modal" class="text-nowrap btn btn-sm btn-outline-secondary mr-2">
        @icon('forward', ['mr' => 2])Send next
  	</a>

  	<a href="#" 
  		data-url="{{route('admin.crashcourses.subscriptions.cancel', $item)}}" 
		data-action="stop {{$item->first_name}}'s subscription"
  		title="Stop subscription" data-toggle="modal" data-target="#confirm-modal" class="text-nowrap btn btn-sm btn-danger">
        @icon('circle-stop', ['mr' => 2])Stop
  	</a>
  	@endif

  @endcomponent
</div>
