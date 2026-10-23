@include('components.bottom-sheet-handle', ['label' => $closeLabel ?? 'Close panel'])
<div class="bottom-sheet__header">
    <div>
        <h2 id="{{ $titleId }}">{{ $title }}</h2>
        @isset($description)<p>{{ $description }}</p>@endisset
    </div>
</div>
