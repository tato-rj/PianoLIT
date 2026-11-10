@component('components.dialogs.modal', ['id' => 'quiz-manage-modal', 'header' => 'Manage topics', 'options' => ['body' => ['padding' => 0]]])
@slot('body')
<div class="px-3 pb-3">
    <form method="POST" action="{{ route('admin.quizzes.topics.store', $returnFilters) }}" class="mb-2">
        @csrf
        <div class="row g-1 align-items-center">
            <div class="col">
                @input(['bag' => 'quizTopicCreate', 'id' => 'quiz-topic-new', 'name' => 'name', 'label' => null, 'placeholder' => 'New topic', 'limit' => 255, 'grid' => 'mb-0', 'classes' => 'form-control-sm', 'useOldInput' => $errors->getBag('quizTopicCreate')->any()])
                <label class="visually-hidden" for="quiz-topic-new">New topic</label>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm" aria-label="Create topic">Create</button></div>
        </div>
    </form>
    <div class="d-flex flex-column gap-1 overflow-auto" style="max-height: min(40vh, 240px)">
    @forelse($topics as $topic)
        <div class="d-flex align-items-center gap-1">
            <form method="POST" action="{{ route('admin.quizzes.topics.update', ['topic' => $topic] + $returnFilters) }}" class="flex-grow-1 m-0">
                @csrf @method('PATCH')
                <div class="row g-1 align-items-center">
                    <div class="col">
                        @input(['bag' => 'quizTopic'.$topic->id, 'id' => 'quiz-topic-'.$topic->id, 'name' => 'name', 'label' => null, 'value' => $topic->name, 'placeholder' => 'Topic name', 'limit' => 255, 'grid' => 'mb-0', 'classes' => 'form-control-sm', 'useOldInput' => $errors->getBag('quizTopic'.$topic->id)->any()])
                        <label class="visually-hidden" for="quiz-topic-{{ $topic->id }}">Topic name: {{ $topic->name }}</label>
                    </div>
                    <div class="col-auto">@include('admin.components.table-action', ['tableAction' => ['kind' => 'other', 'type' => 'submit', 'icon' => 'check', 'iconOnly' => true, 'size' => 'xs', 'label' => 'Save '.$topic->name]])</div>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.quizzes.topics.destroy', ['topic' => $topic] + $returnFilters) }}" class="m-0" onsubmit="return window.confirm('Delete this topic? It will be removed from associated quizzes.')">
                @csrf @method('DELETE')
                @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'size' => 'xs', 'label' => 'Delete '.$topic->name]])
            </form>
        </div>
    @empty
        <p class="text-muted mb-0">No topics yet.</p>
    @endforelse
    </div>
</div>
@endslot
@endcomponent
