<div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
    @if($synthesiaRequest->piece)
        @include('admin.components.table-action', ['tableAction' => ['url' => route('webapp.pieces.show', $synthesiaRequest->piece_id), 'label' => 'View piece', 'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]])
    @endif
    @unless($synthesiaRequest->isPublished())
        <form method="POST" action="{{ route('admin.synthesia-requests.publish', ['synthesiaRequest' => $synthesiaRequest->id] + $returnFilters) }}" class="m-0" onsubmit="return window.confirm('Publish this Synthesia request?')">
            @csrf
            @method('PATCH')
            @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => 'Publish', 'icon' => 'check', 'tone' => 'green']])
        </form>
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.synthesia-requests.destroy', ['synthesiaRequest' => $synthesiaRequest->id] + $returnFilters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal']]])
    @endunless
</div>
