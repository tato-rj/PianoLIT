@php
    $imagePreview = $imagePreview ?? false;
    $isMain = $image['id'] === $mediaState['main_image_id'];
    $rowLabel = 'view '.$viewNumber;
@endphp
<article class="d-flex align-items-center gap-2 py-2 border-bottom" aria-label="{{ $rowLabel }}" data-display-image-row @if($imagePreview) data-ds-preview @endif>
    <img src="{{ ($image['url'] ?? asset('storage/'.$image['path'])) }}" alt="{{ $product->title }} {{ $rowLabel }}" width="48" height="48" class="object-fit-contain rounded flex-shrink-0" loading="lazy">
    <span class="small text-muted">View {{ $viewNumber }}</span>
    @unless($imagePreview)
    <form method="POST" action="{{ route('admin.'.$kind.'.images.action', [$product, $image['id']]) }}" class="ms-auto" onsubmit="return !event.submitter || event.submitter.value !== 'remove' || window.confirm('Remove this display image? You can restore it later.')">
        @csrf @method('PATCH')
        <input type="hidden" name="media_revision" value="{{ $mediaState['revision'] }}">
    @endunless
        <div class="d-flex align-items-center gap-1 ms-auto flex-shrink-0">
            @foreach([
                ['action' => 'up', 'icon' => 'arrow-up', 'label' => 'Move '.$rowLabel.' earlier', 'disabled' => $first],
                ['action' => 'down', 'icon' => 'arrow-down', 'label' => 'Move '.$rowLabel.' later', 'disabled' => $last],
                ['action' => 'main', 'icon' => $isMain ? 'check' : 'star', 'label' => $isMain ? 'Main image: '.$rowLabel : 'Use '.$rowLabel.' as main image', 'disabled' => $isMain],
                ['action' => 'remove', 'icon' => 'trash-2', 'label' => 'Remove '.$rowLabel, 'disabled' => false],
            ] as $control)
                @include('admin.components.table-action', ['tableAction' => [
                    'kind' => $control['action'] === 'remove' ? 'delete' : null, 'type' => $imagePreview ? 'button' : 'submit', 'size' => 'xs',
                    'label' => $control['label'], 'icon' => $control['icon'], 'iconOnly' => true,
                    'attributes' => ['name' => 'action', 'value' => $control['action'], 'disabled' => $control['disabled'] || $imagePreview],
                ]])
            @endforeach
        </div>
    @unless($imagePreview)</form>@endunless
</article>
