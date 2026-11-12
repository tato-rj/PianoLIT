@php
    $hasCourseInput = array_key_exists('title', session()->getOldInput());
    $selectedTopics = old('topics', $hasCourseInput ? [] : $crashcourse->topics->modelKeys());
    $selectedTopics = is_array($selectedTopics) ? $selectedTopics : [];
@endphp
<form id="course-editor" method="POST" action="{{ route('admin.crashcourses.update', ['crashcourse' => $crashcourse] + $returnFilters) }}" autocomplete="off" enctype="multipart/form-data" data-has-old-input="{{ $hasCourseInput ? 'true' : 'false' }}">
    @csrf
    @method('PATCH')
    <div class="admin-editor-grid">
        @component('admin.components.editor.section', [
            'id' => 'course-details',
            'title' => 'Course details',
            'description' => number_format($activeSubscribers).' active '.str_plural('subscriber', $activeSubscribers),
        ])
            @input(['bag' => 'default', 'id' => 'course-title', 'value' => $hasCourseInput ? '' : $crashcourse->title, 'name' => 'title', 'label' => 'Title', 'limit' => 120])
            @textarea(['bag' => 'default', 'id' => 'course-description', 'value' => $hasCourseInput ? '' : $crashcourse->description, 'name' => 'description', 'label' => 'Description', 'limit' => 238, 'rows' => 3])

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
            @image(['name' => '', 'errorKey' => 'cover_image', 'accept' => '.jpg,.jpeg', 'buttonLabel' => $crashcourse->cover_path ? 'Change image' : 'Choose image', 'image' => $crashcourse->cover_path ? $crashcourse->cover_image() : asset('images/misc/placeholder-image-wide.png'), 'empty' => false, 'alt' => $crashcourse->cover_path ? 'Cover image for '.$crashcourse->title : 'No cover image selected'])
            <p class="small text-muted mb-0">JPG · under 1 MB. Confirm the crop before saving.</p>
            <p class="small text-danger mt-2 mb-0" data-course-cover-error role="alert" hidden></p>
        @endcomponent
    </div>

    @component('admin.components.table-save-actions', ['status' => $hasCourseInput ? 'Unsaved changes' : 'Changes take effect when you save.'])
        <button type="submit" id="submit-button" class="btn btn-primary">@icon('check')Save changes</button>
    @endcomponent
</form>
<p class="small text-muted mb-4">Created by {{ optional($crashcourse->creator)->name ?: 'Unknown creator' }}.</p>
