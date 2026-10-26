<div data-piece-tags data-url="{{ route('admin.pieces.update-tag', $piece) }}" data-badge="piece-tags-{{ $piece->id }}">
    @foreach($tagsByType as $type => $tags)
    <fieldset class="mb-4">
        <legend class="fs-6">{{ ucfirst($type) }}</legend>
        <div class="d-flex flex-wrap gap-3">
            @foreach($tags as $tag)
            @include('components.forms.checkbox', ['id' => 'tag-'.$piece->id.'-'.$tag->id, 'name' => 'tags[]', 'value' => $tag->id, 'label' => $tag->name, 'checked' => $piece->tags->contains($tag->id)])
            @endforeach
        </div>
    </fieldset>
    @endforeach
</div>
