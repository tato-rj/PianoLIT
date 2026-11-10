@component('components.dialogs.modal', ['id' => 'blog-topics-modal', 'header' => 'Manage topics', 'options' => []])
@slot('body')
<div>
    <form method="POST" action="{{ route('admin.topics.store', $returnFilters) }}" class="mb-2">
        @csrf
        <div class="row g-1 align-items-center">
            <div class="col">
                @input(['bag' => 'blogTopicCreate', 'id' => 'blog-topic-new', 'name' => 'name', 'label' => null, 'placeholder' => 'New topic', 'limit' => 255, 'grid' => 'mb-0', 'classes' => 'form-control-sm', 'useOldInput' => $errors->getBag('blogTopicCreate')->any()])
                <label class="visually-hidden" for="blog-topic-new">New topic</label>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm" aria-label="Create topic">Create</button></div>
        </div>
    </form>
    <div class="d-flex flex-column gap-1">
    @forelse($topics as $topic)
        <div class="d-flex align-items-center gap-1">
            <form method="POST" action="{{ route('admin.topics.update', ['topic' => $topic] + $returnFilters) }}" class="flex-grow-1 m-0">
                @csrf @method('PATCH')
                <div class="row g-1 align-items-center">
                    <div class="col">
                        @input(['bag' => 'blogTopic'.$topic->id, 'id' => 'blog-topic-'.$topic->id, 'name' => 'name', 'label' => null, 'value' => $topic->name, 'placeholder' => 'Topic name', 'limit' => 255, 'grid' => 'mb-0', 'classes' => 'form-control-sm', 'useOldInput' => $errors->getBag('blogTopic'.$topic->id)->any()])
                        <label class="visually-hidden" for="blog-topic-{{ $topic->id }}">Topic name: {{ $topic->name }}</label>
                    </div>
                    <div class="col-auto">@include('admin.components.table-action', ['tableAction' => ['kind' => 'other', 'type' => 'submit', 'icon' => 'check', 'iconOnly' => true, 'size' => 'xs', 'label' => 'Save '.$topic->name]])</div>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.topics.destroy', ['topic' => $topic] + $returnFilters) }}" class="m-0" onsubmit="return window.confirm('Delete this topic? It will be removed from associated posts.')">
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
