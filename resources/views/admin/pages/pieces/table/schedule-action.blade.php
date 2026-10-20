@include('admin.components.table-action', ['tableAction' => [
    'kind' => 'schedule', 'label' => 'Schedule', 'icon' => 'calendar-days',
    'attributes' => [
        'data-bs-toggle' => 'modal', 'data-bs-target' => '#freepick-schedule-modal',
        'data-freepick-piece' => $item->id, 'data-freepick-name' => $item->long_name,
        'data-freepick-url' => route('admin.pieces.schedule', ['piece' => $item] + ($returnFilters ?? [])),
        'disabled' => !$item->hasImage(),
        'title' => $item->hasImage() ? 'Schedule Freepick' : 'Save a cover image before scheduling this piece',
        'aria-label' => 'Schedule '.$item->long_name.' as Freepick',
    ],
]])
