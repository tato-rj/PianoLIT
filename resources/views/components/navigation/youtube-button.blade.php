<a href="{{ ($designSystemPreview ?? false) ? '#ds-buttons' : 'https://www.youtube.com/pianolit' }}"
   class="btn btn-red btn-sm px-3 px-sm-4 py-2 d-inline-flex align-items-center gap-2 fw-semibold"
   style="--radius-button: var(--radius-pill); --bs-btn-bg: var(--color-black); --bs-btn-border-color: var(--color-black)"
   @if($designSystemPreview ?? false) data-ds-preview @else target="_blank" rel="noopener noreferrer" @endif>
    <span class="d-inline-flex" aria-hidden="true">@fa(['icon' => 'youtube', 'fa_type' => 'b', 'color' => 'white', 'mr' => 0])</span>
    Youtube
    <span class="d-inline-flex" aria-hidden="true">@icon('arrow-up-right', ['mr' => 0])</span>
</a>
