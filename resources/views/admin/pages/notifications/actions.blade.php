<div class="text-end">
    @include('admin.components.table-action', ['tableAction' => ['label' => 'Mark as unread', 'class' => 'mark-notification m-0', 'attributes' => ['style' => 'display: '.($item->read_at ? 'inline-block' : 'none'), 'data-url' => route('admin.notifications.unread', ['ids' => [$item->id]])]]])
    @include('admin.components.table-action', ['tableAction' => ['label' => 'Mark as read', 'class' => 'mark-notification m-0', 'attributes' => ['style' => 'display: '.($item->read_at ? 'none' : 'inline-block'), 'data-url' => route('admin.notifications.read', ['ids' => [$item->id]])]]])
</div>
