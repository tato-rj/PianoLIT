<tr><td colspan="{{ count($columns) }}"><div class="admin-table__empty">
    <h3>{{ $emptyTitle ?? 'No matching results' }}</h3>
    @if(!empty($emptyDescription))<p>{{ $emptyDescription }}</p>@endif
    @if(!empty($emptyAction))<a class="btn btn-light mt-3" href="{{ $emptyAction['url'] }}">{{ $emptyAction['label'] }}</a>@endif
</div></td></tr>
