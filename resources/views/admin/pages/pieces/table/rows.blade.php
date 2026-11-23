@foreach($pieces as $item)
<tr>
    <td class="admin-table__account">
        <a class="admin-table__primary clamp-2" title="{{ $item->long_name }}" href="{{ route('webapp.pieces.show', $item) }}" target="_blank" rel="noopener noreferrer">{{ $item->basename() }}</a>
        @if(!$item->hasAudio())
        <small><a href="{{ youtube($item->long_name.' by '.optional($item->composer)->name) }}" target="_blank" rel="noopener noreferrer">Find a recording @icon('external-link', ['mr' => 0, 'classes' => 'icon-size-xs'])</a></small>
        @endif
    </td>
    <td class="text-nowrap">
        {{ trim($item->catalogue_full_name.' '.$item->catalogue_number) }}
        @if(filled($item->collection_number))
            no.{{ trim($item->collection_number) }}
        @endif
    </td>
    <td class="text-nowrap">@include('components.music.composer-label', ['composer' => $item->composer])</td>
    <td class="text-nowrap">
        <span class="d-inline-flex align-items-center">
            @if($item->level_name)
                @icon('circle', ['mr' => 2, 'classes' => 'color-'.$item->level_name, 'filled' => true])
            @endif
            <span>{{ ucfirst(optional($item->extendedLevel)->name ?? 'Not set') }}</span>
        </span>
    </td>
    <td class="text-nowrap">
        @if($item->is_free)
            <span class="admin-table__badge admin-table__badge--green">Current Freepick</span>
        @elseif($item->highlighted_at)
            <time class="d-block" datetime="{{ $item->highlighted_at->toIso8601String() }}">@icon('calendar-check', ['color' => 'green']){{ $item->highlighted_at->toFormattedDateString() }}</time>
        @endif
        @include('admin.pages.pieces.table.scheduled-date')
    </td>
    @php
        $hasScore = trim((string) $item->score_path) !== '';
        $isEscore = $item->score_source === \App\Services\Pieces\ScoreSource::PIANOLIT;
    @endphp
    <td class="text-nowrap{{ $hasScore && !$isEscore ? ' opacity-6' : '' }}">
        @if($hasScore)
            <a class="text-reset" href="{{ route('admin.pieces.score-preview', $item) }}" target="_blank" rel="noopener noreferrer"
               aria-label="{{ $isEscore ? 'eScore' : 'External score' }} for {{ $item->long_name }} (opens in a new tab)">
                @if($isEscore)
                    @icon('pianolit', ['mr' => 1, 'color' => 'primary'])eScore
                @else
                    @icon('square-arrow-out-up-right', ['mr' => 1])External
                @endif
            </a>
        @endif
    </td>
    <td>
        <div class="d-flex flex-nowrap justify-content-end align-items-center gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.pieces.edit', $item), 'attributes' => ['aria-label' => 'Edit '.$item->long_name]]])
            @include('admin.pages.pieces.table.schedule-action')
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.pieces.destroy', ['piece' => $item] + $returnFilters), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal', 'title' => 'Delete piece', 'aria-label' => 'Delete '.$item->long_name]]])
        </div>
    </td>
</tr>
@endforeach
