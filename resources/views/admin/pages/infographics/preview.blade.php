@component('components.dialogs.modal', ['id' => 'item-preview', 'header' => 'Image preview'])
@slot('body')
<div class="d-flex flex-wrap gap-3 small text-muted mb-3">
    <span>Full image: <span data-dimensions="image"></span></span>
    <span>Thumbnail: <span data-dimensions="thumbnail"></span></span>
</div>
<img class="w-100 image rounded" alt="">
<img class="thumbnail d-none" alt="" aria-hidden="true">
@endslot
@endcomponent
