@php
    $builderJson = old('email_design');
    if (!is_string($builderJson)) $builderJson = json_encode($emailDesign, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $icons = ['text' => 'type', 'hero' => 'layout-template', 'image' => 'image', 'button' => 'mouse-pointer-2', 'panel' => 'square', 'columns' => 'columns-2', 'divider' => 'minus', 'spacer' => 'move-vertical'];
@endphp
<section class="email-builder mb-4" aria-labelledby="email-builder-title" data-email-builder
    data-has-old-input="{{ session()->hasOldInput() ? 'true' : 'false' }}"
    data-preview-url="{{ route('admin.crashcourses.lessons.preview-design', $crashcourse) }}"
    data-upload-url="{{ route('admin.crashcourses.lessons.images', $crashcourse) }}"
    data-library-url="{{ route('admin.crashcourses.email-blocks.index') }}">
    <textarea name="email_design" data-design hidden>{{ $builderJson }}</textarea>
    <header class="email-builder__header">
        <div><h2 id="email-builder-title">Email builder</h2><p>Drag a component into the email, or click to add it.</p></div>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-sm btn-secondary" data-builder-action="undo" disabled title="Undo">@icon('undo-2', ['mr' => 0])<span class="visually-hidden">Undo</span></button>
            <button type="button" class="btn btn-sm btn-secondary" data-builder-action="redo" disabled title="Redo">@icon('redo-2', ['mr' => 0])<span class="visually-hidden">Redo</span></button>
            <button type="button" class="btn btn-sm btn-dark" data-builder-action="preview">@icon('eye')Preview</button>
        </div>
    </header>
    @include('components.forms.error', ['field' => 'email_design', 'errorClasses' => 'd-block px-3'])
    <div class="email-builder__workspace">
        <aside class="email-builder__palette" aria-label="Email components">
            <h3>Components</h3>
            <div class="email-builder__components">
                @foreach(\App\Services\CrashCourses\EmailDesign::TYPES as $type => $label)
                <button type="button" class="btn btn-secondary email-builder__component" draggable="true" data-add-block="{{ $type }}">@icon($icons[$type], ['mr' => 0])<span>{{ $label }}</span></button>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-secondary w-100 mt-3" data-builder-action="starter">@icon('layout-template')Add lesson layout</button>
            <details class="email-builder__library" open>
                <summary>Saved blocks</summary>
                <p class="small text-muted">Reuse a saved block in any lesson.</p>
                <div data-block-library><p class="small text-muted">Loading saved blocks…</p></div>
            </details>
            <details class="email-builder__settings">
                <summary>Email style</summary>
                @include('components.forms.select', ['bag' => 'default', 'name' => 'builder_font', 'id' => 'builder-font', 'label' => 'Font', 'choices' => ['sans' => 'Sans serif', 'serif' => 'Serif'], 'required' => false])
                <div class="email-builder__colors">
                    <div><label for="builder-background">Background</label>@include('components.forms.color', ['id' => 'builder-background', 'name' => 'builder_background', 'value' => '#ffffff'])</div>
                    <div><label for="builder-color">Text</label>@include('components.forms.color', ['id' => 'builder-color', 'name' => 'builder_color', 'value' => '#242933'])</div>
                </div>
                @input(['bag' => 'default', 'id' => 'builder-background-image', 'name' => 'builder_background_image', 'label' => 'Background image URL', 'required' => false, 'placeholder' => 'https://…'])
                <button type="button" class="btn btn-sm btn-secondary" data-upload-target="settings">@icon('upload')Upload background</button>
                <p class="small text-muted mt-2 mb-0">The background color appears when an inbox cannot show background images.</p>
            </details>
        </aside>
        <div class="email-builder__stage">
            <div class="email-builder__viewport-controls" role="group" aria-label="Preview size">
                <button type="button" class="btn btn-sm btn-secondary" data-device="desktop" aria-pressed="true">@icon('monitor')Desktop</button>
                <button type="button" class="btn btn-sm btn-secondary" data-device="phone" aria-pressed="false">@icon('smartphone')Phone</button>
                <span class="small text-muted" data-preview-status role="status">Preparing preview…</span>
            </div>
            <div class="email-builder__canvas-scroll">
                <div class="email-builder__empty" data-empty-canvas hidden>@icon('layout-template', ['classes' => 'icon-size-xl', 'mr' => 0])<h3>Your email is empty</h3><p>Add components from the left, or start with a lesson layout.</p><button type="button" class="btn btn-dark btn-sm" data-builder-action="starter">Add lesson layout</button></div>
                <div class="email-builder__canvas-frame" data-canvas-frame><iframe class="email-builder__canvas" title="Lesson email canvas" sandbox="allow-same-origin" data-email-canvas></iframe></div>
            </div>
            <p class="small text-muted email-builder__canvas-note">Click a block to edit it. Drag its handle to change the order.</p>
        </div>
        <aside class="email-builder__inspector" aria-label="Selected block settings">
            <div data-no-selection><h3>Block settings</h3><p class="small text-muted">Select a block in the email to edit its content and appearance.</p></div>
            <div data-block-inspector hidden>
                <div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0" data-selected-title>Text</h3><span class="small text-muted" data-block-position></span></div>
                <div class="email-builder__block-actions" role="group" aria-label="Block actions">
                    @foreach(['up' => ['arrow-up', 'Move up'], 'down' => ['arrow-down', 'Move down'], 'duplicate' => ['copy', 'Duplicate block'], 'remove' => ['trash-2', 'Remove block']] as $action => $meta)
                    <button type="button" class="btn btn-sm btn-secondary" data-builder-action="{{ $action }}" title="{{ $meta[1] }}" aria-label="{{ $meta[1] }}">@icon($meta[0], ['mr' => 0])</button>
                    @endforeach
                </div>
                <div data-for-types="hero panel">@input(['bag' => 'default', 'id' => 'block-title', 'name' => 'block_title', 'label' => 'Heading', 'required' => false, 'limit' => 300])</div>
                <div data-for-types="text hero panel columns">
                    @tinyeditor(['bag' => 'default', 'id' => 'block-content', 'name' => 'block_content', 'label' => 'Text', 'rows' => 8])
                </div>
                <div data-for-types="columns">
                    @tinyeditor(['bag' => 'default', 'id' => 'block-content-right', 'name' => 'block_content_right', 'label' => 'Right column', 'rows' => 8])
                </div>
                <div data-for-types="image hero panel">
                    @input(['bag' => 'default', 'id' => 'block-image', 'name' => 'block_image', 'label' => 'Image URL', 'required' => false, 'placeholder' => 'https://…'])
                    <button type="button" class="btn btn-sm btn-secondary mb-3" data-upload-target="image">@icon('upload')Upload image</button>
                    @input(['bag' => 'default', 'id' => 'block-alt', 'name' => 'block_alt', 'label' => 'Image description', 'required' => false, 'limit' => 300])
                    @input(['bag' => 'default', 'id' => 'block-image-width', 'name' => 'block_image_width', 'label' => 'Image width (px)', 'type' => 'number', 'min' => 1, 'limit' => 504, 'required' => false])
                </div>
                <div data-for-types="button hero panel">@input(['bag' => 'default', 'id' => 'block-label', 'name' => 'block_label', 'label' => 'Button text', 'required' => false, 'limit' => 300])</div>
                <div data-for-types="button hero panel image">@input(['bag' => 'default', 'id' => 'block-url', 'name' => 'block_url', 'label' => 'Link URL', 'required' => false, 'placeholder' => 'https://…'])</div>
                <details open><summary>Appearance</summary>
                    @include('components.forms.select', ['bag' => 'default', 'id' => 'block-align', 'name' => 'block_align', 'label' => 'Alignment', 'choices' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'], 'required' => false])
                    <div class="email-builder__colors">
                        <div><label for="block-background">Background</label>@include('components.forms.color', ['id' => 'block-background', 'name' => 'block_background', 'value' => '#ffffff'])</div>
                        <div><label for="block-color">Text</label>@include('components.forms.color', ['id' => 'block-color', 'name' => 'block_color', 'value' => '#242933'])</div>
                        <div data-for-types="button hero panel"><label for="block-button-color">Button</label>@include('components.forms.color', ['id' => 'block-button-color', 'name' => 'block_button_color', 'value' => '#0055fe'])</div>
                    </div>
                    @input(['bag' => 'default', 'id' => 'block-background-image', 'name' => 'block_background_image', 'label' => 'Background image URL', 'required' => false, 'placeholder' => 'https://…'])
                    <button type="button" class="btn btn-sm btn-secondary mb-3" data-upload-target="background_image">@icon('upload')Upload background</button>
                    <div class="row gx-2">
                        <div class="col-6">@input(['bag' => 'default', 'id' => 'block-padding', 'name' => 'block_padding', 'label' => 'Padding (px)', 'type' => 'number', 'min' => 0, 'limit' => 48, 'required' => false])</div>
                        <div class="col-6">@input(['bag' => 'default', 'id' => 'block-gap', 'name' => 'block_gap', 'label' => 'Space below (px)', 'type' => 'number', 'min' => 0, 'limit' => 64, 'required' => false])</div>
                    </div>
                    @input(['bag' => 'default', 'id' => 'block-radius', 'name' => 'block_radius', 'label' => 'Corners (px)', 'type' => 'number', 'min' => 0, 'limit' => 32, 'required' => false])
                    <div data-for-types="spacer">@input(['bag' => 'default', 'id' => 'block-height', 'name' => 'block_height', 'label' => 'Height (px)', 'type' => 'number', 'min' => 0, 'limit' => 120, 'required' => false])</div>
                </details>
                <details><summary>Save for reuse</summary>
                    @input(['bag' => 'default', 'id' => 'block-save-name', 'name' => 'block_save_name', 'label' => 'Block name', 'required' => false, 'limit' => 80, 'placeholder' => 'For example, Practice activity'])
                    <button type="button" class="btn btn-secondary btn-sm" data-builder-action="save-block">@icon('bookmark-plus')Save block</button>
                    <p class="small text-muted mt-2">Saved blocks are shared with other editors. Adding one creates an independent copy.</p>
                </details>
            </div>
        </aside>
    </div>
    <p class="email-builder__status small mb-0" role="status" aria-live="polite" data-builder-status>Changes take effect when you save the lesson.</p>
    <div class="email-builder__upload" data-upload-panel hidden>
        <div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0">Upload an image</h3><button type="button" class="btn btn-sm btn-secondary" data-builder-action="cancel-upload">Cancel</button></div>
        @include('components.forms.upload.dropzone', ['id' => 'email-builder-upload', 'name' => 'builder_file', 'label' => 'Image', 'accept' => '.jpg,.jpeg,.png,.gif', 'maxBytes' => 4194304, 'hint' => 'JPG, PNG or GIF · up to 4 MB and 4000 × 4000 pixels', 'required' => false])
        <button type="button" class="btn btn-sm btn-primary" data-builder-action="upload">Upload image</button>
    </div>
</section>
