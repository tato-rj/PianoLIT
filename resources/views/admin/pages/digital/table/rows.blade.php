@foreach($products as $product)
@php
    $routeParameters = [$kind === 'ebooks' ? 'ebook' : 'escore' => $product] + $returnFilters;
    $imageUrl = $mainImages->get(get_class($product).':'.$product->id);
@endphp
<tr>
    <td class="text-nowrap">{{ optional($product->created_at)->format('M j, Y') ?: 'Not set' }}</td>
    <td>
        <div class="d-flex align-items-center gap-3">
            @if($imageUrl)<img src="{{ $imageUrl }}" alt="" width="48" height="64" class="object-fit-contain flex-shrink-0" loading="lazy">@endif
            <div class="admin-table__account">
                <a href="{{ route($kind.'.show', $product) }}" target="_blank" rel="noopener noreferrer" class="admin-table__primary clamp-2" title="{{ $product->title }}">{{ $product->title }}</a>
                @if($product->author)<small>{{ $product->author }}</small>@endif
            </div>
        </div>
    </td>
    <td><a href="{{ route('admin.'.$kind.'.index', $returnFilters + ['purchases' => $product->id]) }}#digital-purchases-modal" aria-label="View purchases of {{ $product->title }}">{{ number_format($product->purchases_count) }}</a></td>
    <td class="text-nowrap">
        <form method="POST" action="{{ route('admin.'.$kind.'.update-status', $routeParameters + ['return_to' => 'directory']) }}" class="m-0">
            @csrf @method('PATCH')
            <input type="hidden" name="published" value="0">
            @toggle(['toggle' => $product->published_at, 'name' => 'published', 'inputValue' => '1', 'ariaLabel' => 'Published: '.$product->title, 'submitOnChange' => true])
            <small>{{ $product->published_at ? 'Published' : 'Draft' }}</small>
            <noscript><button type="submit" class="btn btn-dark btn-sm">Save</button></noscript>
        </form>
    </td>
    <td>
        <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.'.$kind.'.edit', $routeParameters), 'attributes' => ['aria-label' => 'Edit '.$product->title]]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'other', 'label' => 'Preview '.$product->title, 'icon' => 'eye', 'iconOnly' => true, 'url' => route($kind.'.show', $product), 'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.'.$kind.'.destroy', $routeParameters + ['return_to' => 'directory']), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'aria-label' => 'Delete '.$product->title]]])
        </div>
    </td>
</tr>
@endforeach
