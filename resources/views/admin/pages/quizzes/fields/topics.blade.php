@component('admin.components.editor.section', ['id' => 'quiz-topics', 'title' => 'Topics'])
    <div class="d-flex flex-wrap gap-2">
        @forelse($topics as $topic)
        @include('components.forms.checkbox', ['id' => 'quiz-topic-'.$topic->id, 'name' => 'topics[]', 'value' => $topic->id, 'label' => $topic->name, 'checked' => in_array($topic->id, $selectedTopics), 'classes' => 'me-2 mb-0'])
        @empty
        <p class="small text-muted mb-0">No topics yet. Add topics from the Quizzes page.</p>
        @endforelse
    </div>
    @include('components.forms.error', ['field' => 'topics', 'errorClasses' => 'd-block'])
    @include('components.forms.error', ['field' => 'topics.*', 'errorClasses' => 'd-block'])
@endcomponent
