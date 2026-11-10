@component('admin.components.editor.section', ['id' => 'post-references', 'title' => 'References', 'description' => 'Optional sources and citations for this article.', 'class' => 'mb-4'])
    @slot('actions')
        <button type="button" class="btn btn-secondary btn-sm" data-add-reference>@icon('plus')Add reference</button>
    @endslot
    <div class="d-flex flex-column gap-2" data-reference-rows>
        @foreach($references as $index => $reference)
            @include('admin.pages.blog.post.references.input', ['referenceIndex' => $index, 'referenceValue' => is_string($reference) ? $reference : ''])
        @endforeach
    </div>
    @include('components.forms.error', ['field' => 'references', 'errorClasses' => 'd-block'])
    <template data-reference-template>
        @include('admin.pages.blog.post.references.input', ['referenceIndex' => '__INDEX__', 'referenceValue' => '', 'templateReference' => true])
    </template>
@endcomponent
