<div @if($dragToClose ?? false) data-sheet-draggable @endif class="offcanvas offcanvas-bottom bottom-sheet {{ $classes ?? '' }}" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $titleId }}" @foreach($data ?? [] as $attribute => $value) data-{{ $attribute }}="{{ $value }}" @endforeach>
    @if($dragToClose ?? false)
        @include('components.dialogs.bottom-sheet.handle', ['label' => $closeLabel ?? 'Close panel', 'draggable' => true])
    @endif
    <div class="offcanvas-body" @isset($bodyId) id="{{ $bodyId }}" @endisset>{{ $slot }}</div>
</div>
