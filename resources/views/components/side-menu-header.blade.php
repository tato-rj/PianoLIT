<div class="offcanvas-header side-menu-header gap-3 px-4 py-3">
    <h2 class="offcanvas-title h6 mb-0" id="{{ $titleId }}">{{ $title }}</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ $closeLabel ?? 'Close' }}" @if($designSystemPreview ?? false) data-ds-preview @endif></button>
</div>
