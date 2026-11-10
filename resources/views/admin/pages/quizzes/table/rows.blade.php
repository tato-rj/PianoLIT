@foreach($quizzes as $quiz)
<tr>
    <td class="text-nowrap"><time datetime="{{ $quiz->created_at->toDateString() }}">{{ $quiz->created_at->format('M j, Y') }}</time></td>
    <td>
        <a class="admin-table__primary" href="{{ route('admin.quizzes.edit', ['quiz' => $quiz] + $returnFilters) }}">{{ $quiz->title }}</a>
        <small>{{ optional($quiz->level)->name ?: 'No level' }}@if($quiz->topics->isNotEmpty()) · {{ $quiz->topics->pluck('name')->join(', ') }}@endif</small>
    </td>
    <td class="text-nowrap">{{ count($quiz->questions) }}</td>
    <td>
        <form method="POST" action="{{ route('admin.quizzes.update-status', ['quiz' => $quiz] + $returnFilters) }}" class="m-0">
            @csrf @method('PATCH')
            <input type="hidden" name="return_to" value="directory">
            <input type="hidden" name="published" value="0">
            @toggle(['toggle' => $quiz->published_at, 'name' => 'published', 'inputValue' => '1', 'ariaLabel' => 'Published: '.$quiz->title, 'submitOnChange' => true])
            <noscript><button type="submit" class="btn btn-dark btn-sm">Save</button></noscript>
        </form>
    </td>
    <td><div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
        @include('admin.pages.quizzes.table.preview', ['previewQuiz' => $quiz])
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.quizzes.edit', ['quiz' => $quiz] + $returnFilters), 'attributes' => ['aria-label' => 'Edit '.$quiz->title]]])
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.quizzes.destroy', ['quiz' => $quiz] + $returnFilters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'aria-label' => 'Delete '.$quiz->title]]])
    </div></td>
</tr>
@endforeach
