@foreach($infographs as $infograph)
@php($routeParameters = ['infograph' => $infograph] + $returnFilters)
<tr>
    <td class="text-nowrap">{{ optional($infograph->created_at)->format('M j, Y') ?: 'Not set' }}</td>
    <td>
        <div class="d-flex align-items-center gap-3">
            @if($infograph->cover_path)<img src="{{ storage($infograph->thumbnail_path) }}" alt="" width="48" height="64" class="object-fit-contain flex-shrink-0" loading="lazy">@endif
            <div class="admin-table__account">
                <a href="{{ $infograph->showRoute() }}" target="_blank" rel="noopener noreferrer" class="admin-table__primary clamp-2">{{ $infograph->name }}</a>
                @if($infograph->topics->isNotEmpty())<small>{{ $infograph->topics->pluck('name')->implode(', ') }}</small>@endif
            </div>
        </div>
    </td>
    <td>{{ number_format($infograph->downloads) }}</td>
    <td>{{ number_format($infograph->score) }}</td>
    <td>
        <form method="POST" action="{{ route('admin.infographs.update-status', $routeParameters + ['return_to' => 'directory']) }}" class="m-0">
            @csrf @method('PATCH')
            <input type="hidden" name="attribute" value="published_at">
            <input type="hidden" name="enabled" value="0">
            @toggle(['toggle' => $infograph->published_at, 'name' => 'enabled', 'inputValue' => '1', 'ariaLabel' => 'Published: '.$infograph->name, 'submitOnChange' => true])
            <noscript><button type="submit" class="btn btn-dark btn-sm">Save</button></noscript>
        </form>
    </td>
    <td>
        <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.infographs.edit', $routeParameters), 'attributes' => ['aria-label' => 'Edit '.$infograph->name]]])
            @include('admin.components.table-action', ['tableAction' => ['label' => 'Preview '.$infograph->name, 'icon' => 'eye', 'iconOnly' => true, 'attributes' => ['data-bs-toggle' => 'modal', 'data-bs-target' => '#item-preview', 'data-name' => $infograph->name, 'data-image' => storage($infograph->cover_path), 'data-thumbnail' => storage($infograph->thumbnail_path)]]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.infographs.destroy', $routeParameters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'aria-label' => 'Delete '.$infograph->name]]])
        </div>
    </td>
</tr>
@endforeach
