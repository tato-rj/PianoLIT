@php
    $hasCourseInput = array_key_exists('title', session()->getOldInput());
    $selectedTopics = old('topics', $editing && !$hasCourseInput ? $crashcourse->topics->modelKeys() : []);
    $selectedTopics = is_array($selectedTopics) ? $selectedTopics : [];
    $hasCover = !empty($crashcourse->cover_path);
    $formAction = $editing
        ? route('admin.crashcourses.update', ['crashcourse' => $crashcourse] + $returnFilters)
        : route('admin.crashcourses.store', $returnFilters);
    $saveStatus = $hasCourseInput ? 'Unsaved changes' : ($editing ? 'Changes take effect when you save.' : 'Not saved yet');
@endphp
<form id="course-editor" method="POST" action="{{ $formAction }}" autocomplete="off" enctype="multipart/form-data" data-has-old-input="{{ $hasCourseInput ? 'true' : 'false' }}">
    @csrf
    @if($editing) @method('PATCH') @endif
    <div class="admin-editor-grid">
        @component('admin.components.editor.section', [
            'id' => 'course-details',
            'title' => 'Course details',
            'description' => $editing ? number_format($activeSubscribers).' active '.str_plural('subscriber', $activeSubscribers) : null,
        ])
            @input(['bag' => 'default', 'id' => 'course-title', 'value' => $hasCourseInput ? '' : ($crashcourse->title ?? ''), 'name' => 'title', 'label' => 'Title', 'limit' => 120])
            @textarea(['bag' => 'default', 'id' => 'course-description', 'value' => $hasCourseInput ? '' : ($crashcourse->description ?? ''), 'name' => 'description', 'label' => 'Description', 'limit' => 238, 'rows' => 3])

            <fieldset>
                <legend class="small fw-semibold mb-2">Topics</legend>
                <div class="d-flex flex-wrap gap-3">
                    @forelse($topics as $topic)
                        @include('components.forms.checkbox', ['id' => 'course-topic-'.$topic->id, 'name' => 'topics[]', 'value' => $topic->id, 'label' => $topic->name, 'checked' => in_array($topic->id, $selectedTopics), 'classes' => 'mb-0'])
                    @empty
                        <p class="small text-muted mb-0">No topics yet.</p>
                    @endforelse
                </div>
                @include('components.forms.error', ['field' => 'topics', 'errorClasses' => 'd-block'])
                @include('components.forms.error', ['field' => 'topics.*', 'errorClasses' => 'd-block'])
            </fieldset>
        @endcomponent

        @component('admin.components.editor.section', ['id' => 'course-cover', 'title' => 'Cover image'])
            @image(['name' => '', 'errorKey' => 'cover_image', 'accept' => '.jpg,.jpeg', 'buttonLabel' => $hasCover ? 'Change image' : 'Choose image', 'image' => $hasCover ? $crashcourse->cover_image() : asset('images/misc/placeholder-image-wide.png'), 'empty' => false, 'alt' => $hasCover ? 'Cover image for '.$crashcourse->title : 'No cover image selected'])
            <p class="small text-muted mb-0">JPG · under 1 MB. Confirm the crop before saving.</p>
            <p class="small text-danger mt-2 mb-0" data-course-cover-error role="alert" hidden></p>
        @endcomponent
    </div>

    @component('admin.components.table-save-actions', ['status' => $saveStatus])
        <button type="submit" id="submit-button" class="btn btn-primary">@icon($editing ? 'check' : 'plus'){{ $editing ? 'Save changes' : 'Create course' }}</button>
    @endcomponent
</form>
@if($editing)
    <p class="small text-muted mb-4">Created by {{ optional($crashcourse->creator)->name ?: 'Unknown creator' }}.</p>
@endif
