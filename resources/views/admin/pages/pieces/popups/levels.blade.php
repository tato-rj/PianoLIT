<fieldset data-piece-level data-url="{{ route('admin.pieces.update-level', $piece) }}" data-badge="piece-level-{{ $piece->id }}" data-old-level="{{ optional($piece->level)->id }}">
    <legend class="fs-6">Choose a level</legend>
    <div class="d-flex flex-wrap gap-3">
        @foreach($levels as $level)
        @include('components.form.checkbox', ['type' => 'radio', 'id' => 'level-'.$piece->id.'-'.$level->id, 'name' => 'level-'.$piece->id, 'value' => $level->id, 'label' => ucfirst($level->name), 'checked' => optional($piece->level)->id === $level->id])
        @endforeach
    </div>
</fieldset>
