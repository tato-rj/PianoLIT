@foreach($posts as $post)
@php($parameters = ['post' => $post] + $returnFilters)
<tr>
    <td class="text-nowrap">{{ optional($post->created_at)->format('M j, Y') ?: 'Not set' }}</td>
    <td>
        <div class="admin-table__account">
            @if($post->published_at)
                <a href="{{ route('posts.show', $post) }}" target="_blank" rel="noopener noreferrer" class="admin-table__primary clamp-2">{{ $post->title }}</a>
            @else
                <span class="admin-table__primary clamp-2">{{ $post->title }}</span>
            @endif
            @if($post->topics->isNotEmpty())<small>{{ $post->topics->pluck('name')->join(', ') }}</small>@endif
        </div>
    </td>
    <td class="text-nowrap">{{ $post->reading_time }} min</td>
    <td class="text-nowrap">
        <form method="POST" action="{{ route('admin.posts.update-status', $parameters + ['return_to' => 'directory']) }}" class="m-0">
            @csrf @method('PATCH')
            <input type="hidden" name="published" value="0">
            @toggle(['toggle' => $post->published_at, 'name' => 'published', 'inputValue' => '1', 'ariaLabel' => 'Published: '.$post->title, 'submitOnChange' => true])
            <small>{{ $post->published_at ? 'Published' : 'Draft' }}</small>
            <noscript><button type="submit" class="btn btn-dark btn-sm">Save</button></noscript>
        </form>
    </td>
    <td>
        <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.posts.edit', $parameters), 'attributes' => ['aria-label' => 'Edit '.$post->title]]])
            @include('admin.pages.blog.table.preview', ['previewPost' => $post])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.posts.destroy', $parameters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'aria-label' => 'Delete '.$post->title]]])
        </div>
    </td>
</tr>
@endforeach
