<div class="d-flex justify-content-end align-items-center gap-2">
    @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.users.show', $item)]])
    @include('admin.components.table-action', ['tableAction' => ['url' => route('impersonate', $item), 'label' => 'Impersonate user', 'icon' => 'hat-glasses', 'iconOnly' => true, 'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]])
</div>
