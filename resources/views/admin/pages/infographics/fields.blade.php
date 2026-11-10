@php
    $record = $infograph ?? null;
    $failed = $errors->getBag('infographic')->any();
    $selectedTopics = $failed ? old('topics', []) : ($record ? $record->topics->pluck('id')->all() : []);
    $selectedTopics = is_array($selectedTopics) ? array_filter($selectedTopics, 'is_scalar') : [];
@endphp
@input(['bag' => 'infographic', 'id' => 'infographic-name', 'name' => 'name', 'label' => 'Name', 'value' => $record ? $record->name : '', 'limit' => 120, 'useOldInput' => $failed])
@textarea(['bag' => 'infographic', 'id' => 'infographic-description', 'name' => 'description', 'label' => 'Description', 'value' => $record ? $record->description : '', 'oldInputKey' => $failed ? 'description' : '__unused_description', 'limit' => 238, 'rows' => 3])
@include('components.forms.upload.dropzone', [
    'bag' => 'infographic', 'id' => 'infographic-image', 'name' => 'cover_image',
    'label' => $record ? 'Replace image' : 'Image', 'accept' => 'image/jpeg', 'required' => !$record,
    'compact' => true, 'hint' => $record ? 'JPG · up to 10 MB. Leave empty to keep the current image.' : 'JPG · up to 10 MB',
])
@if($topics->isNotEmpty())
@options(['bag' => 'infographic', 'type' => 'checkbox', 'name' => 'topics', 'label' => 'Topics', 'options' => $topics->pluck('id', 'name'), 'values' => $selectedTopics, 'required' => false, 'errorClasses' => 'd-block'])
@else
<p class="small text-muted">No topics yet. Add topics from Manage topics on the Infographics page.</p>
@endif
@include('components.forms.error', ['bag' => 'infographic', 'field' => 'topics.*', 'errorClasses' => 'd-block'])
