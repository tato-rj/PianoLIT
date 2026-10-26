@foreach($composers as $composer)
<tr>
    <td>
        <a class="admin-table__primary" href="{{ route('admin.composers.edit', ['composer' => $composer] + $returnFilters) }}">@include('components.music.composer-label')</a>
        @if($composer->alive_on)<small>{{ $composer->alive_on }}</small>@endif
    </td>
    <td class="text-nowrap">{{ $composer->period ?: 'Not set' }}@if($composer->is_pedagogical)<small>Pedagogical</small>@endif</td>
    <td>{{ optional($composer->country)->nationality ?: 'Not set' }}</td>
    <td>
        <form method="POST" class="m-0" action="{{ route('admin.composers.toggle-famous', ['composer' => $composer, 'return_to' => 'directory'] + $returnFilters) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="is_famous" value="0">
            @toggle(['toggle' => $composer->is_famous, 'name' => 'is_famous', 'inputValue' => '1', 'ariaLabel' => 'Famous: '.$composer->name, 'submitOnChange' => true])
            <noscript><button class="btn btn-dark btn-sm" type="submit">Save</button></noscript>
        </form>
    </td>
    <td class="text-nowrap"><a href="{{ route('admin.composers.edit', ['composer' => $composer] + $returnFilters) }}#composer-repertoire" aria-label="View repertoire by {{ $composer->name }}">{{ number_format($composer->pieces_count) }} {{ str_plural('piece', $composer->pieces_count) }}</a></td>
    <td>
        <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.composers.edit', ['composer' => $composer] + $returnFilters), 'attributes' => ['aria-label' => 'Edit '.$composer->name]]])
            @can('update', $composer)
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.composers.destroy', ['composer' => $composer] + $returnFilters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'aria-label' => 'Delete '.$composer->name]]])
            @endcan
        </div>
    </td>
</tr>
@endforeach
