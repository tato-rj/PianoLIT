@component('components.accordion-item', [
    'id' => 'chord-'.$index.'-step-'.$step,
    'parentId' => 'chord-accordion-'.$index,
    'title' => $step.'. '.ucfirst(strtolower($title)),
    'open' => !empty($show),
])
    {{ $slot }}
@endcomponent
