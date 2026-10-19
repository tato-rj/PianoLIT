@php
    $legacyFilterFields = [];
    foreach ($filters ?? [] as $legacyFilter) {
        $legacyFilterOptions = $legacyFilter === 'composers'
            ? \App\Composer::all()->mapWithKeys(function ($composer) { return [$composer->id => ucwords($composer->name)]; })->all()
            : \App\Tag::$legacyFilter()->get()->mapWithKeys(function ($tag) { return [$tag->name => ucwords($tag->name)]; })->all();
        $legacyFilterFields[] = ['name' => str_singular($legacyFilter), 'label' => ucfirst(str_singular($legacyFilter)), 'options' => ['' => 'Any '.str_singular($legacyFilter)] + $legacyFilterOptions, 'submitOnChange' => true];
    }
    $legacyFilterId = 'legacy-directory-'.\Illuminate\Support\Str::uuid();
@endphp
@component('admin.components.filters.form', ['id' => $legacyFilterId, 'label' => 'Directory filters', 'fields' => $legacyFilterFields, 'state' => request()->query(), 'resetUrl' => request()->url()])
    @include('components.form.checkbox', ['id' => $legacyFilterId.'-creator', 'name' => 'creator_id', 'value' => auth('admin')->id(), 'label' => 'Created by me', 'checked' => request('creator_id') == auth('admin')->id(), 'submitOnChange' => true])
    @foreach(['missing_itunes' => 'Missing iTunes links', 'missing_youtube' => 'Missing YouTube videos', 'missing_audio' => 'Missing audio', 'missing_score' => 'Missing score'] as $name => $label)
        @include('components.form.checkbox', ['id' => $legacyFilterId.'-'.$name, 'name' => $name, 'value' => 'on', 'label' => $label, 'checked' => (bool) request($name), 'submitOnChange' => true])
    @endforeach
    @if(!empty($sortable))
        @foreach(['asc' => 'Ascending', 'desc' => 'Descending'] as $value => $label)
            @include('components.form.checkbox', ['id' => $legacyFilterId.'-order-'.$value, 'type' => 'radio', 'name' => 'order', 'value' => $value, 'label' => $label, 'checked' => request('order') === $value, 'submitOnChange' => true])
        @endforeach
    @endif
@endcomponent
