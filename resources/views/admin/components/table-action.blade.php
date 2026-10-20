@php
    $tableActionKind = $tableAction['kind'] ?? 'other';
    $tableActionTone = ['edit' => 'warning', 'delete' => 'danger'][$tableActionKind] ?? 'dark';
    $tableActionLabel = $tableAction['label'] ?? (['edit' => 'Edit', 'delete' => 'Delete'][$tableActionKind] ?? 'Open');
    $tableActionIcon = $tableAction['icon'] ?? (['edit' => 'square-pen', 'delete' => 'trash-2'][$tableActionKind] ?? null);
    $tableActionIconOnly = $tableAction['iconOnly'] ?? ($tableActionKind === 'delete');
    $tableActionTag = isset($tableAction['url']) ? 'a' : 'button';
    $tableActionAttributes = array_merge(['title' => $tableActionLabel, 'aria-label' => $tableActionLabel], $tableAction['attributes'] ?? []);
@endphp
<{{ $tableActionTag }} @if(isset($tableAction['url'])) href="{{ $tableAction['url'] }}" @else type="{{ $tableAction['type'] ?? 'button' }}" @endif class="btn btn-{{ $tableActionTone }} btn-sm text-nowrap {{ $tableActionKind === 'edit' ? 'text-dark' : 'text-white' }} {{ $tableAction['class'] ?? '' }}" data-table-action="{{ $tableActionKind }}" @foreach($tableActionAttributes as $tableActionAttribute => $tableActionValue) @if($tableActionValue === true) {{ $tableActionAttribute }} @elseif($tableActionValue !== false && $tableActionValue !== null) {{ $tableActionAttribute }}="{{ $tableActionValue }}" @endif @endforeach>
    @if($tableActionIcon)
        @icon($tableActionIcon, ['mr' => $tableActionIconOnly ? 0 : 2])
    @endif
    @if(!$tableActionIconOnly){{ $tableActionLabel }}@endif
</{{ $tableActionTag }}>
