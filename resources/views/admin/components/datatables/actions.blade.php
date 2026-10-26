<div class="d-flex flex-nowrap justify-content-end align-items-center gap-2">
    {{ $slot ?? null }}
    @foreach($actions as $folder => $action)
        @include('admin.components.datatables.actions.'.$folder)
    @endforeach
</div>
