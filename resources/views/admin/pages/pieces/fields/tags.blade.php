@component('admin.components.editor.section', ['id' => 'piece-tags', 'title' => 'Tags'])
    @if($warnOnManyTags ?? false)
    <div data-tag-warning @if(count($selectedTags) <= 7) hidden @endif>
        @alert(['color' => 'yellow', 'message' => 'Try to keep the selection between 5 and 7 tags.'])
    </div>
    @endif
    @foreach($types as $type => $tags)
    <fieldset class="mb-4">
        <legend class="small text-muted mb-2">{{ ucfirst($type) }}</legend>
        <div class="d-flex flex-wrap gap-2">
            @foreach($tags as $tag)
                @include('components.forms.checkbox', ['id' => 'piece-tag-'.$tag->id, 'name' => 'tags[]', 'value' => $tag->id, 'label' => ucfirst($tag->name), 'checked' => in_array($tag->id, $selectedTags), 'classes' => 'me-2 mb-0'])
            @endforeach
        </div>
    </fieldset>
    @endforeach
    <p class="small text-muted mb-0">Special tags: {{ $specialTags }}</p>
@endcomponent
