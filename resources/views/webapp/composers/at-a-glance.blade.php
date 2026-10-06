@php
    $details = [
        ['icon' => 'user', 'label' => 'Full name', 'value' => $composer->name],
        ['icon' => 'calendar', 'label' => 'Born', 'value' => $composer->born_at],
        ['icon' => 'map-pin', 'label' => 'Died', 'value' => $composer->died_at],
        ['icon' => 'flag', 'label' => 'Nationality', 'value' => optional($composer->country)->name],
        ['icon' => 'music', 'label' => 'Era', 'value' => $composer->period ? $composer->period.' era' : null],
        ['icon' => 'piano', 'label' => 'Instrument', 'value' => 'Piano'],
    ];
@endphp
<aside class="border rounded p-4" aria-labelledby="composer-glance-title">
    <h2 id="composer-glance-title" class="h5 d-flex align-items-center gap-3 mb-3">
        @icon('chart-no-axes-column', ['color' => 'muted', 'mr' => 0])At a glance
    </h2>
    <dl class="mb-0">
        @foreach($details as $detail)
        <div class="row g-2 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
            <dt class="col-6 fw-normal text-muted d-flex align-items-start gap-2">
                @icon($detail['icon'], ['mr' => 0, 'classes' => 'icon-fw mt-1'])
                <span class="small">{{ $detail['label'] }}</span>
            </dt>
            <dd class="col-6 mb-0 text-break small">{{ $detail['value'] ?: '—' }}</dd>
        </div>
        @endforeach
    </dl>
</aside>
