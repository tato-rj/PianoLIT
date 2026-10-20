<div class="d-flex flex-nowrap justify-content-end align-items-center gap-2">
    @include('admin.components.table-action', ['tableAction' => ['url' => 'mailto:'.$item->email, 'label' => 'Contact subscriber', 'icon' => 'mail', 'iconOnly' => true, 'attributes' => ['aria-label' => 'Contact '.$item->email]]])
    @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'attributes' => ['data-url' => route('admin.subscriptions.destroy', ['subscription' => $item] + ($returnFilters ?? [])), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'title' => 'Delete subscriber', 'aria-label' => 'Delete '.$item->email]]])
</div>
