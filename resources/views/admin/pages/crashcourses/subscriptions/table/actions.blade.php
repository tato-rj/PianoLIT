<div class="text-end">
  @component('admin.components.datatables.actions', ['actions' => []])

	@if(! $item->isCompleted && ! $item->isCancelled)
    @include('admin.components.table-action', ['tableAction' => ['label' => 'Resend', 'icon' => 'rotate-cw', 'attributes' => ['data-url' => route('admin.crashcourses.subscriptions.resend', $item), 'data-action' => 'resend the last lesson to '.$item->email, 'data-bs-toggle' => 'modal', 'data-bs-target' => '#confirm-modal']]])

	@include('admin.components.table-action', ['tableAction' => ['label' => 'Send next', 'icon' => 'forward', 'attributes' => ['data-url' => route('admin.crashcourses.subscriptions.next', $item), 'data-action' => 'send the next lesson to '.$item->email, 'data-bs-toggle' => 'modal', 'data-bs-target' => '#confirm-modal']]])

	@include('admin.components.table-action', ['tableAction' => ['label' => 'Stop', 'icon' => 'circle-stop', 'attributes' => ['data-url' => route('admin.crashcourses.subscriptions.cancel', $item), 'data-action' => 'stop '.$item->first_name."'s subscription", 'data-bs-toggle' => 'modal', 'data-bs-target' => '#confirm-modal']]])
	@endif

  @endcomponent
</div>
