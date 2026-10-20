<form method="POST" action="{{ route('admin.feedback.destroy', ['feedback' => $submission] + $returnFilters + ($page > 1 ? ['page' => $page] : [])) }}" class="m-0">
    @csrf
    @method('DELETE')
    @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'label' => 'Delete feedback']])
</form>
