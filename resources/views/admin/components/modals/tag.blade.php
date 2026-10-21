@php($tagFormFailed = $errors->getBag('tagForm')->any())
@component('components.modal', ['id' => 'tag-modal', 'header' => $editingTag ? 'Edit tag' : 'Create a tag', 'show' => $tagFormFailed ? true : null])
@slot('body')
    <form method="POST" id="tag-form"
        action="{{ $editingTag ? route('admin.tags.update', ['tag' => $editingTag->id] + $returnFilters) : route('admin.tags.store', $returnFilters) }}"
        data-create-url="{{ route('admin.tags.store', $returnFilters) }}" data-default-type="{{ $filters['tag_type'] }}">
        @csrf
        <input type="hidden" name="_method" value="PATCH" data-tag-method @unless($editingTag) disabled @endunless>
        <p class="text-muted">Use a clear name and choose the type that fits the piece.</p>
        @if($tagFormFailed)<div class="alert alert-red" role="alert" data-tag-errors>Please check the highlighted fields.</div>@endif
        @input(['bag' => 'tagForm', 'id' => 'tag-name', 'name' => 'name', 'label' => 'Tag name', 'value' => $tagFormFailed ? '' : optional($editingTag)->name, 'limit' => 255, 'placeholder' => 'e.g. lyrical'])
        @select(['bag' => 'tagForm', 'id' => 'tag-type', 'name' => 'type', 'label' => 'Type', 'choices' => ['' => 'Choose a type'] + $tagTypes, 'value' => $tagFormFailed ? '' : (optional($editingTag)->type ?? $filters['tag_type'])])
        @input(['bag' => 'tagForm', 'id' => 'tag-ordering', 'name' => 'ordering', 'label' => 'Display order (optional)', 'type' => 'number', 'min' => 1, 'step' => 1, 'limit' => 65535, 'required' => '', 'value' => $tagFormFailed ? '' : optional($editingTag)->ordering, 'placeholder' => 'Leave empty for no order', 'describedBy' => 'tag-ordering-help'])
        <p class="small text-muted" id="tag-ordering-help">Controls the order of periods and genres on the website. Lower numbers appear first; leave empty to list after numbered tags.</p>
        <p class="small text-muted" data-tag-creator @unless($editingTag) hidden @endunless>Created by <span>{{ optional(optional($editingTag)->creator)->name ?? 'Not recorded' }}</span></p>
        <div class="d-flex justify-content-end flex-wrap gap-2 mt-4">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark" data-tag-save>{{ $editingTag ? 'Save changes' : 'Create tag' }}</button>
        </div>
    </form>
@endslot
@endcomponent
