@component('admin.components.editor.section', ['id' => 'infographic-topics', 'title' => 'Topics'])
    @if($topics->isNotEmpty())
    <div class="d-flex flex-wrap gap-2" role="group" aria-labelledby="infographic-topics-title">
        @foreach($topics as $topic)
            @include('components.forms.checkbox', ['id' => 'infographic-topic-'.$topic->id, 'name' => 'topics[]', 'value' => $topic->id, 'label' => $topic->name, 'checked' => in_array($topic->id, $selectedTopics), 'classes' => 'me-2 mb-0'])
        @endforeach
    </div>
    @else
    <p class="small text-muted mb-0">No topics yet. Add topics from Manage topics on the Infographics page.</p>
    @endif
    @include('components.forms.error', ['bag' => 'infographic', 'field' => 'topics', 'errorClasses' => 'd-block'])
    @include('components.forms.error', ['bag' => 'infographic', 'field' => 'topics.*', 'errorClasses' => 'd-block'])
@endcomponent
