@php
    $subtitles = [
        'all' => 'Web views since '.$report['webViewsFrom'].' plus recorded iOS views.',
        'web' => 'Google Analytics views on my.pianolit.com since '.$report['webViewsFrom'].'.',
        'ios' => 'Historical views, legacy app piece requests, and confirmed opens.',
    ];
    $colors = ['all' => '#a692ce', 'web' => '#83c9df', 'ios' => '#efb07c'];
@endphp
@component('admin.pages.analytics.pieces.ranking', [
    'id' => 'piece-views',
    'title' => 'Most viewed pieces',
    'subtitle' => $subtitles[$platform],
    'rows' => $report['views'],
    'unit' => $platform === 'web' ? 'web views' : 'views',
    'color' => $colors[$platform],
])
    @component('admin.components.filters.form', [
        'id' => 'piece-view-filters',
        'label' => 'Piece view filters',
        'action' => route('admin.analytics.pieces').'#piece-views',
        'class' => 'mb-0',
        'fields' => [[
            'name' => 'view_platform',
            'label' => 'Platform',
            'options' => \App\Analytics\PieceReport::VIEW_PLATFORMS,
            'submitOnChange' => true,
        ]],
        'state' => ['view_platform' => $platform],
        'hidden' => ['months' => $report['months']],
        'buttons' => [],
    ])
        <noscript>@include('admin.components.filters.button', ['button' => ['label' => 'Apply']])</noscript>
    @endcomponent
@endcomponent
