@php
    $pieceTabs = [
        ['target' => 'tab-about', 'label' => 'About', 'data' => ['anchor' => 'about']],
        ['target' => 'tab-score', 'label' => 'Score', 'data' => ['anchor' => 'score']],
        ['target' => 'tab-synthesia', 'label' => 'Synthesia', 'data' => ['anchor' => 'synthesia']],
    ];
    if ($timeline->isNotEmpty()) $pieceTabs[] = ['target' => 'tab-timeline', 'label' => 'Timeline', 'data' => ['anchor' => 'timeline']];
@endphp
@component('components.navigation.tab-navigation', [
    'id' => 'main-nav', 'label' => 'Piece sections', 'variant' => 'segmented-bar', 'class' => 'mb-4',
    'items' => $pieceTabs, 'active' => 'tab-about', 'syncHash' => false,
])
@endcomponent
