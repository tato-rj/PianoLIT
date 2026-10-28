<section class="design-system__section" id="ds-buttons" aria-labelledby="ds-buttons-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">06 / ACTIONS</span><h2 id="ds-buttons-title">Buttons</h2><p>Hover, press or tab to a button to inspect its current state. These samples do not save or submit anything.</p></header>
  <div class="design-system__grid">
    @foreach(['primary' => 'Primary', 'secondary' => 'Secondary', 'outline-primary' => 'Outline primary', 'outline-secondary' => 'Outline secondary', 'success' => 'Success', 'danger' => 'Danger', 'light' => 'Light', 'dark' => 'Dark'] as $variant => $label)
      @component('admin.pages.settings.sample', ['label' => $label, 'usage' => '.btn .btn-'.$variant, 'properties' => 'font-size,line-height,padding,color,background-color,border,border-radius,box-shadow'])
        <button type="button" class="btn btn-{{ $variant }}" data-ds-preview>{{ $label }}</button>
      @endcomponent
    @endforeach
  </div>
  <h3 class="design-system__group-title">Sizes</h3>
  <div class="design-system__grid">
    @foreach(['xs' => 'Extra small', 'sm' => 'Small', '' => 'Default', 'lg' => 'Large', 'xl' => 'Extra large'] as $size => $label)
      @component('admin.pages.settings.sample', ['label' => $label, 'usage' => '.btn .btn-primary'.($size ? ' .btn-'.$size : '')])
        <button type="button" class="btn btn-primary {{ $size ? 'btn-'.$size : '' }}" data-ds-preview>{{ $label }}</button>
      @endcomponent
    @endforeach
  </div>
  <h3 class="design-system__group-title">States & icon actions</h3>
  <div class="design-system__grid">
    @component('admin.pages.settings.sample', ['label' => 'Active', 'usage' => '.btn .btn-primary .active'])
      <button type="button" class="btn btn-primary active" data-ds-preview>Active</button>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Disabled', 'usage' => '.btn .btn-primary [disabled]'])
      <button type="button" class="btn btn-primary" disabled data-ds-preview>Disabled</button>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'With icon', 'usage' => '.btn .btn-secondary + Lucide icon'])
      <button type="button" class="btn btn-secondary" data-ds-preview>@icon('plus')Add piece</button>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Guest account', 'usage' => 'components.account.account-circle · .btn-raw + user-round', 'properties' => 'width,height,font-size,color,background-color,border'])
      @include('components.account.account-circle', ['account' => null, 'designSystemPreview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Youtube link', 'usage' => 'components.navigation.youtube-button · dark pill', 'properties' => 'font-size,padding,color,background-color,border-radius'])
      @include('components.navigation.youtube-button', ['designSystemPreview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Public hub menu', 'usage' => 'components.navigation.hub-menu.button · Bootstrap offcanvas', 'properties' => 'padding,color'])
      @include('components.navigation.hub-menu.button', ['designSystemPreview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Signed-in account', 'usage' => 'components.account.account-circle · first initial', 'properties' => 'width,height,font-size,color,background-color,border'])
      @include('components.account.account-circle', ['account' => (object) ['first_name' => 'Arthur'], 'menuId' => 'ds-account-circle', 'designSystemPreview' => true])
    @endcomponent
  </div>
</section>

<section class="design-system__section" id="ds-inputs" aria-labelledby="ds-inputs-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">07 / FORMS</span><h2 id="ds-inputs-title">Inputs & selection</h2><p>Shared form fields with their real focus, validation and disabled styles. Values stay on this page.</p></header>
  <div class="design-system__grid">
    @foreach(['sm' => 'Small', '' => 'Default', 'lg' => 'Large'] as $size => $label)
      @component('admin.pages.settings.sample', ['label' => $label.' input', 'usage' => '@input · components.forms.input'.($size ? ' · .form-control-'.$size : '')])
        @input(['bag' => 'default', 'id' => 'ds-input-'.($size ?: 'default'), 'name' => 'ds_piece_title_'.($size ?: 'default'), 'label' => 'Piece title', 'classes' => $size ? 'form-control-'.$size : '', 'value' => '', 'placeholder' => 'Search the repertoire', 'required' => false, 'autocomplete' => 'off', 'designSystemPreview' => true])
      @endcomponent
    @endforeach
    @component('admin.pages.settings.sample', ['label' => 'Select', 'usage' => '@select · components.forms.select'])
      @select(['bag' => 'default', 'id' => 'ds-select', 'name' => 'ds_level', 'label' => 'Level', 'choices' => ['' => 'All levels', 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'], 'value' => '', 'required' => false, 'designSystemPreview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Calendar with unavailable dates', 'usage' => 'components.forms.date-picker · shared field and button styles'])
      @include('components.forms.date-picker', ['bag' => 'default', 'id' => 'ds-calendar', 'name' => 'ds_calendar', 'label' => 'Date', 'value' => '', 'minDate' => now()->toDateString(), 'blockedDates' => [now()->addDay()->toDateString()], 'required' => false, 'designSystemPreview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Filter select', 'usage' => 'admin.components.filters.select · @select'])
      @include('admin.components.filters.select', ['label' => 'Period', 'name' => 'ds-period', 'options' => \App\Support\AdminDateRanges::LABELS, 'value' => '7', 'designSystemPreview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Date filter', 'usage' => 'admin.components.filters.date-range · .admin-filter-select'])
      @include('admin.components.filters.date-range', ['label' => 'Custom range', 'fieldLabel' => 'Dates', 'showLabel' => true, 'designSystemPreview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Textarea', 'usage' => 'textarea.form-control'])
      <label for="ds-textarea">Notes</label><textarea id="ds-textarea" class="form-control" rows="3" placeholder="Add a practice note" data-text-improver="off" data-ds-preview></textarea>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Star rating', 'usage' => 'components.forms.rating · native required radio group'])
      @include('components.forms.rating', ['id' => 'ds-rating', 'name' => 'ds_rating', 'value' => 4])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Input group', 'usage' => '.input-group + .input-group-text'])
      <label for="ds-group">Website</label><div class="input-group"><span class="input-group-text">https://</span><input id="ds-group" type="text" class="form-control" placeholder="pianolit.com" data-ds-preview></div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Valid', 'usage' => '.form-control .is-valid'])
      <label for="ds-valid">Collection name</label><input id="ds-valid" class="form-control is-valid" value="Evening practice" aria-describedby="ds-valid-note" data-ds-preview><div class="valid-feedback" id="ds-valid-note">This name is available.</div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Invalid', 'usage' => '.form-control .is-invalid'])
      <label for="ds-invalid">Email</label><input id="ds-invalid" type="email" class="form-control is-invalid" value="example" aria-invalid="true" aria-describedby="ds-invalid-note" data-ds-preview><div class="invalid-feedback" id="ds-invalid-note">Enter a valid email address.</div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Disabled', 'usage' => '.form-control [disabled]'])
      <label for="ds-disabled">Unavailable field</label><input id="ds-disabled" class="form-control" value="Disabled input" disabled data-ds-preview>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'File upload', 'usage' => '.form-control [type=file]'])
      @include('components.forms.upload.file', ['id' => 'ds-upload', 'name' => 'ds_upload', 'label' => 'Choose a local file', 'designSystemPreview' => true])
      @include('components.forms.upload.file', ['id' => 'ds-upload-multiple', 'name' => 'ds_upload_multiple[]', 'label' => 'Choose multiple local files', 'multiple' => true, 'accept' => 'image/jpeg,image/png,image/webp', 'designSystemPreview' => true])
      <small>Sample only. No file is uploaded.</small>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Checkboxes', 'usage' => '.form-check-input', 'properties' => 'width,height,border,border-radius,background-color'])
      @include('components.forms.checkbox', ['id' => 'ds-checkbox', 'name' => 'ds_checkbox', 'label' => 'Save to favorites', 'checked' => true, 'designSystemPreview' => true])
      @include('components.forms.checkbox', ['id' => 'ds-checkbox-disabled', 'name' => 'ds_checkbox_disabled', 'label' => 'Disabled option', 'disabled' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Compact checkboxes', 'usage' => 'components.forms.checkbox · .small .text-muted'])
      <div class="small text-muted">
        @include('components.forms.checkbox', ['id' => 'ds-checkbox-compact', 'name' => 'ds_checkbox_compact', 'label' => 'Missing video', 'classes' => 'mb-0', 'designSystemPreview' => true])
      </div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Radio buttons', 'usage' => '.form-check-input [type=radio]', 'properties' => 'width,height,border,border-radius,background-color'])
      <div class="form-check"><input id="ds-radio-one" class="form-check-input" type="radio" name="ds-format" checked data-ds-preview><label class="form-check-label" for="ds-radio-one">Audio</label></div>
      <div class="form-check"><input id="ds-radio-two" class="form-check-input" type="radio" name="ds-format"><label class="form-check-label" for="ds-radio-two">Video</label></div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Checkbox menu', 'usage' => 'components.forms.checkbox-menu'])
      @include('components.forms.checkbox-menu', ['name' => 'ds-platform', 'label' => 'Platform', 'options' => \App\Activity\Filters::PLATFORMS, 'selected' => ['webapp', 'app'], 'allLabel' => 'All platforms', 'designSystemPreview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Switch', 'usage' => '@toggle · components.forms.toggle', 'properties' => 'width,height,border-radius,background-color'])
      <div class="design-system__row">@toggle(['toggle' => true, 'autoToggle' => false, 'ariaLabel' => 'Enable practice reminders', 'designSystemPreview' => true])<span>Practice reminders</span></div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Range', 'usage' => '.form-range', 'properties' => 'width,height'])
      <label for="ds-range">Volume</label><input id="ds-range" type="range" class="form-range" min="0" max="100" value="60" data-ds-preview>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Color', 'usage' => 'components.forms.color · .form-control-color'])
      <label for="ds-color">Background color</label>
      @include('components.forms.color', ['id' => 'ds-color', 'name' => 'ds_color', 'value' => '#0055fe', 'designSystemPreview' => true])
    @endcomponent
  </div>
</section>
