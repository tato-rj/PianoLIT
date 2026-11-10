@component('components.dialogs.modal', ['id' => 'infographic-topics-modal', 'header' => 'Manage topics'])
@slot('body')
<form method="POST" action="{{ route('admin.infographs.topics.store', $returnFilters) }}" class="mb-3">
    @csrf
    <div class="row g-2 align-items-start">
        <div class="col">
            @input(['bag' => 'infographicTopicCreate', 'id' => 'infographic-topic-new', 'name' => 'name', 'label' => null, 'placeholder' => 'New topic', 'limit' => 255, 'grid' => 'mb-0', 'classes' => 'form-control-sm', 'useOldInput' => $errors->getBag('infographicTopicCreate')->any()])
            <label class="visually-hidden" for="infographic-topic-new">New topic</label>
        </div>
        <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Create topic</button></div>
    </div>
</form>
<div class="d-flex flex-column gap-2">
@forelse($topics as $topic)
    <div class="d-flex align-items-start gap-2">
        <form method="POST" action="{{ route('admin.infographs.topics.update', ['topic' => $topic] + $returnFilters) }}" class="flex-grow-1 m-0">
            @csrf @method('PATCH')
            <div class="row g-2 align-items-start">
                <div class="col">
                    @input(['bag' => 'infographicTopic'.$topic->id, 'id' => 'infographic-topic-'.$topic->id, 'name' => 'name', 'label' => null, 'value' => $topic->name, 'placeholder' => 'Topic name', 'limit' => 255, 'grid' => 'mb-0', 'classes' => 'form-control-sm', 'useOldInput' => $errors->getBag('infographicTopic'.$topic->id)->any()])
                    <label class="visually-hidden" for="infographic-topic-{{ $topic->id }}">Topic name: {{ $topic->name }}</label>
                </div>
                <div class="col-auto">@include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'icon' => 'check', 'iconOnly' => true, 'size' => 'xs', 'label' => 'Save '.$topic->name]])</div>
            </div>
        </form>
        <form method="POST" action="{{ route('admin.infographs.topics.destroy', ['topic' => $topic] + $returnFilters) }}" class="m-0" onsubmit="return window.confirm('Delete this topic? It will be removed from associated infographics.')">
            @csrf @method('DELETE')
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'size' => 'xs', 'label' => 'Delete '.$topic->name]])
        </form>
    </div>
@empty
    <p class="text-muted mb-0">No topics yet.</p>
@endforelse
</div>
@endslot
@endcomponent
