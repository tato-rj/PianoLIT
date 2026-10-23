<div class="offcanvas offcanvas-bottom bottom-sheet {{ $classes ?? '' }}" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $titleId }}" @foreach($data ?? [] as $attribute => $value) data-{{ $attribute }}="{{ $value }}" @endforeach>
    <div class="offcanvas-body" @isset($bodyId) id="{{ $bodyId }}" @endisset>{{ $slot }}</div>
</div>
