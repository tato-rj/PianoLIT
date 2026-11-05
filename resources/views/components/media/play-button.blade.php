<button type="button" class="btn btn-primary btn-lg rounded-circle p-0 d-inline-flex align-items-center justify-content-center position-relative"
    style="width: clamp(64px, 8vw, 88px); height: clamp(64px, 8vw, 88px); --radius-button: var(--radius-circle)"
    @isset($id) id="{{ $id }}" @endisset
    @isset($modalId) aria-haspopup="dialog" aria-controls="{{ $modalId }}" @endisset
    @isset($descriptionId) aria-describedby="{{ $descriptionId }}" @endisset
    @foreach($data ?? [] as $name => $value) data-{{ $name }}="{{ $value }}" @endforeach
    @if($designSystemPreview ?? false) data-ds-preview @endif>
    @icon('play', ['mr' => 0, 'solid' => true, 'styles' => 'font-size: 32px'])
    <span class="{{ ($caption ?? false) ? 'position-absolute top-100 start-50 translate-middle-x mt-3 text-white fw-bold text-uppercase text-nowrap fs-6' : 'visually-hidden' }}" @foreach($labelData ?? [] as $name => $value) data-{{ $name }}="{{ $value }}" @endforeach>{{ $label ?? 'Play' }}</span>
</button>
