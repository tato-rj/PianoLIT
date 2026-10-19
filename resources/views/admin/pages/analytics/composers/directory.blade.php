<section id="composer-directory" class="ca-section ca-directory" aria-labelledby="ca-directory-title">
    <div class="ca-section-heading"><div><h2 id="ca-directory-title">Explore composers</h2><p>Find a composer and review repertoire depth and audience interest. Filters change this directory only.</p></div><span>{{ number_format($directory->total()) }} {{ $directory->total() === 1 ? 'composer' : 'composers' }}</span></div>
    @component('admin.components.table', [
        'id' => 'composer-directory-table', 'label' => 'Composer directory', 'items' => $directory->fragment('composer-directory'), 'pageSizes' => [], 'tableClass' => 'admin-composers-table',
        'action' => route('admin.analytics.composers'), 'filterAction' => route('admin.analytics.composers').'#composer-directory', 'state' => $filters + ['sort' => 'pieces'],
        'search' => ['label' => 'Composer name', 'placeholder' => 'Search composers', 'maxlength' => 160],
        'filters' => [
            ['name' => 'depth', 'label' => 'Catalogue size', 'options' => ['' => 'All sizes'] + \App\Analytics\ComposerReport::DEPTHS],
            ['name' => 'period', 'label' => 'Period', 'options' => ['' => 'All periods'] + collect($periodKeys)->mapWithKeys(function ($period) { return [$period => ucfirst($period)]; })->all() + ['__missing' => 'Period not recorded']],
            ['name' => 'profile', 'label' => 'Profile gap', 'options' => ['' => 'All profiles', 'biography' => 'No biography', 'portrait' => 'No portrait', 'country' => 'No country record']],
            ['name' => 'coverage', 'label' => 'Coverage gap', 'options' => ['' => 'All coverage', 'videos' => 'No videos', 'folders' => 'Not in users’ folders', 'playlists' => 'Not in playlists']],
        ],
        'sorts' => ['pieces' => 'Most pieces', 'views' => 'Most views', 'saves' => 'Most saves', 'name' => 'Composer name', 'newest' => 'Newest profiles', 'birth' => 'Earliest birth date'],
        'showReset' => (bool) count(array_filter($filters)), 'resetUrl' => route('admin.analytics.composers').'#composer-directory',
        'columns' => ['Composer', 'Pieces', 'Views', 'Saves', 'Born / died', 'Age / lifespan', 'Action'],
        'emptyTitle' => $directory->total() ? 'No composers on this page' : ($summary['total'] ? 'No composers match these filters' : 'No composers yet'),
        'emptyDescription' => $directory->total() ? 'Return to the first page of these results.' : ($summary['total'] ? 'Try another name or adjust the filters.' : 'Composer records will appear here as the catalogue grows.'),
        'emptyAction' => $directory->total() ? ['label' => 'First page', 'url' => route('admin.analytics.composers', array_diff_key($filters, ['page' => true])).'#composer-directory'] : ($summary['total'] ? ['label' => 'Show all composers', 'url' => route('admin.analytics.composers').'#composer-directory'] : null),
    ])
        @foreach($directory as $composer)
        <tr><td class="admin-table__account"><a class="admin-table__primary" href="{{ route('admin.composers.edit', $composer['id']) }}">{{ $composer['name'] }}</a><small>{{ $composer['period'] ? ucfirst($composer['period']) : 'Period not recorded' }} · {{ $composer['country'] ?: 'Country not recorded' }}</small></td><td>{{ number_format($composer['pieces']) }}</td><td>{{ number_format($composer['views']) }}</td><td>{{ number_format($composer['saves']) }}<small>{{ number_format($composer['savers']) }} {{ $composer['savers'] === 1 ? 'account' : 'accounts' }}</small></td><td class="ca-dates">{{ $composer['birth_label'] }}<small>{{ $composer['death_label'] }}</small></td><td>{{ $composer['age'] ?? '—' }}@if($composer['age'] !== null)<small>{{ $composer['death_exact'] ? 'at death' : 'if living' }}</small>@endif</td><td><a class="ca-edit" href="{{ route('admin.composers.edit', $composer['id']) }}">Edit @icon('arrow-up-right', ['mr' => 0])</a></td></tr>
        @endforeach
    @endcomponent
</section>
