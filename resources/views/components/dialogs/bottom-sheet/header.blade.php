@unless($dragToClose ?? false)
    @include('components.dialogs.bottom-sheet.handle', ['label' => $closeLabel ?? 'Close panel'])
@endunless
<div class="bottom-sheet__header">
    @if($back ?? false)
        <button type="button" class="btn btn-secondary btn-sm" data-settings-back hidden aria-label="Back to settings">@icon('arrow-left', ['mr' => 0])</button>
    @endif
    <div class="flex-grow-1">
        <h2 id="{{ $titleId }}">{{ $title }}</h2>
        @isset($description)<p>{{ $description }}</p>@endisset
    </div>
</div>
