@php($lessonRoutes = compact('crashcourse', 'lesson'))
<tr data-lesson-id="{{ $lesson->id }}">
    <td class="text-nowrap align-middle">
        <div class="d-flex align-items-center gap-2">
            @if($lessons->count() > 1)
                <span class="sort-handle px-1" title="Drag to reorder" aria-hidden="true">@icon('grip-vertical', ['mr' => 0])</span>
            @endif
            <span data-lesson-position>{{ $loop->iteration }}</span>
        </div>
    </td>
    <td class="align-middle text-break">
        <a class="admin-table__primary" href="{{ route('admin.crashcourses.lessons.edit', $lessonRoutes) }}">{{ $lesson->subject }}</a>
    </td>
    <td class="text-nowrap align-middle">{{ $lesson->reading_time }} min</td>
    <td class="align-middle">
        <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
            @include('admin.components.table-action', ['tableAction' => ['label' => 'Preview', 'icon' => 'eye', 'attributes' => [
                'data-bs-toggle' => 'modal', 'data-bs-target' => '#lesson-'.$lesson->id.'-preview-modal', 'aria-label' => 'Preview '.$lesson->subject,
            ]]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'tone' => 'secondary', 'url' => route('admin.crashcourses.lessons.edit', $lessonRoutes), 'attributes' => ['aria-label' => 'Edit '.$lesson->subject]]])
            @include('admin.components.table-action', ['tableAction' => ['url' => route('admin.crashcourses.lessons.duplicate', $lessonRoutes), 'label' => 'Duplicate', 'icon' => 'copy', 'attributes' => ['aria-label' => 'Duplicate '.$lesson->subject]]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => [
                'data-url' => route('admin.crashcourses.lessons.destroy', $lessonRoutes), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'aria-label' => 'Delete '.$lesson->subject,
            ]]])
        </div>
    </td>
</tr>
