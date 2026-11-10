@foreach($crashcourses as $crashcourse)
@php($parameters = ['crashcourse' => $crashcourse] + $returnFilters)
<tr>
    <td class="text-nowrap">{{ optional($crashcourse->created_at)->format('M j, Y') ?: 'Not set' }}</td>
    <td>
        <div class="admin-table__account">
            <a href="{{ route('crashcourses.show', $crashcourse) }}" target="_blank" rel="noopener noreferrer" class="admin-table__primary clamp-2">{{ $crashcourse->title }}</a>
            @if($crashcourse->topics->isNotEmpty())<small>{{ $crashcourse->topics->pluck('name')->join(', ') }}</small>@endif
        </div>
    </td>
    <td>{{ number_format($crashcourse->lessons_count) }}</td>
    <td>{{ number_format($crashcourse->directory_active_subscriptions_count) }}</td>
    <td class="text-nowrap">
        <form method="POST" action="{{ route('admin.crashcourses.update-status', $parameters + ['return_to' => 'directory']) }}" class="m-0">
            @csrf @method('PATCH')
            <input type="hidden" name="published" value="0">
            @toggle(['toggle' => $crashcourse->published_at, 'name' => 'published', 'inputValue' => '1', 'ariaLabel' => 'Published: '.$crashcourse->title, 'submitOnChange' => true])
            <small>{{ $crashcourse->published_at ? 'Published' : 'Draft' }}</small>
            <noscript><button type="submit" class="btn btn-dark btn-sm">Save</button></noscript>
        </form>
    </td>
    <td>
        <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.crashcourses.edit', $parameters), 'attributes' => ['aria-label' => 'Edit '.$crashcourse->title]]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'other', 'label' => 'Preview '.$crashcourse->title, 'icon' => 'eye', 'iconOnly' => true, 'url' => route('crashcourses.show', $crashcourse), 'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.crashcourses.destroy', $parameters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'aria-label' => 'Delete '.$crashcourse->title]]])
        </div>
    </td>
</tr>
@endforeach
