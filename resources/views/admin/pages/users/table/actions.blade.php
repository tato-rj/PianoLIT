<div class="d-flex justify-content-end align-items-center gap-2">
    @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.users.show', $item)]])
    @unless(\App\Activity\ExcludedAccounts::user($item))
        @include('admin.components.table-action', ['tableAction' => ['url' => route('admin.analytics.users.profile', ['user' => $item->id, 'tab' => 'visits']), 'label' => 'Logs & analytics', 'icon' => 'chart-line', 'iconOnly' => true]])
    @endunless
</div>
