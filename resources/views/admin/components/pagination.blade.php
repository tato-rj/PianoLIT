@if($paginator->count() || $paginator->hasPages())
<div class="admin-pagination">
    <p class="admin-pagination__summary">Showing {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }} of {{ number_format($paginator->total()) }} {{ $unit ?? 'results' }}@if(!empty($note)) · {{ $note }}@endif</p>
    {{ $paginator->onEachSide(1)->links('admin.components.table-pagination', ['label' => $label ?? 'Results', 'preview' => $preview ?? false]) }}
</div>
@endif
