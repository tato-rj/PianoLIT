<fieldset class="folder-color-picker">
    @php($selectedColor = old('color_picker') === $pickerId && in_array(old('color'), \App\Support\FolderPalette::COLORS, true) ? old('color') : $selectedColor)
    <input type="hidden" name="color_picker" value="{{ $pickerId }}">
    <legend class="text-muted"><small>Folder color</small></legend>
    <div class="folder-color-picker__options">
        @foreach(\App\Support\FolderPalette::COLORS as $color)
            <label class="folder-color-picker__option" data-color="{{ $color }}" title="{{ ucfirst($color) }}">
                <input class="visually-hidden" type="radio" name="color" value="{{ $color }}" id="{{ $pickerId }}-{{ $color }}" required {{ $selectedColor === $color ? 'checked' : '' }}>
                <span class="folder-color-picker__swatch" aria-hidden="true">@icon('check', ['mr' => 0])</span>
                <span class="visually-hidden">{{ ucfirst($color) }}</span>
            </label>
        @endforeach
    </div>
    @include('components.forms.error', ['field' => 'color'])
</fieldset>
