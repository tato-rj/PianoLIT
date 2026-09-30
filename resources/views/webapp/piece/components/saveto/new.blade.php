<div class="save-to-panel__create">
    <button type="button" class="save-to-panel__create-button new-folder" data-target="#new-folder-container-{{ $piece->id }}" id="new-folder-button-{{ $piece->id }}">
        <span class="save-to-panel__create-icon" aria-hidden="true">@icon('plus', ['mr' => 0])</span>
        <span>Create a new folder</span>
        @icon('chevron-right', ['mr' => 0, 'classes' => 'save-to-panel__create-arrow'])
    </button>

    <div class="save-to-panel__form" style="display: none;" id="new-folder-container-{{ $piece->id }}">
        <label for="folder-name-{{ $piece->id }}">New folder name</label>
        <div class="save-to-panel__form-fields">
            <input type="text" id="folder-name-{{ $piece->id }}" name="name" class="form-control" placeholder="Folder name" maxlength="80">
            <div class="save-to-panel__form-actions">
                <button type="button" class="btn btn-primary" data-submit="folder" data-name="#folder-name-{{ $piece->id }}"
                    data-url="{{ route('webapp.users.favorites.folders.store', ['piece_id' => $piece->id]) }}">Save</button>
                <button type="button" class="btn btn-secondary cancel-new-folder" data-container="#new-folder-container-{{ $piece->id }}" data-target="#new-folder-button-{{ $piece->id }}">Cancel</button>
            </div>
        </div>
        <div class="invalid-feedback" role="alert"></div>
    </div>
</div>
