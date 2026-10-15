<form method="POST" action="{{ route('admin.feedback.destroy', ['feedback' => $submission] + $returnFilters + ($page > 1 ? ['page' => $page] : [])) }}" class="m-0">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn-raw text-muted p-2" aria-label="Delete feedback" title="Delete feedback">@icon('trash-2', ['mr' => 0])</button>
</form>
