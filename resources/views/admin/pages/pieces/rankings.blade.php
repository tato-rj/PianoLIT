@php($rankingService = new \App\Services\Rankings)
@component('components.dialogs.modal', ['id' => $ranking.'-modal', 'options' => ['size' => 'lg', 'header' => ['raw' => true], 'body' => ['padding' => 3]]])
    @slot('header')
        <h2 class="h5 mb-0">Highlights from {{ strtoupper($ranking) }} Levels</h2>
        <p class="small text-muted mt-1 mb-0">Syllabus {{ $rankingService->year($ranking) }}</p>
    @endslot
    @slot('body')
        @component('components.layout.accordion', ['id' => 'accordion-'.$ranking])
            @foreach($rankingService->get($ranking) as $list)
                @component('components.layout.accordion-item', ['id' => $ranking.'-collapse-'.$loop->iteration, 'parentId' => 'accordion-'.$ranking, 'title' => 'Level '.$loop->iteration])
                    <ol class="list-unstyled mb-0">
                        @foreach($list as $name => $composer)
                        <li class="d-flex align-items-baseline gap-2 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                            <span class="badge bg-light text-muted">{{ $loop->iteration }}</span>
                            <a target="_blank" rel="noopener noreferrer" href="{{ youtube($name.' by '.$composer) }}" class="small link-blue"><strong>{{ $name }}</strong> <span class="text-muted">by {{ $composer }}</span></a>
                        </li>
                        @endforeach
                    </ol>
                @endcomponent
            @endforeach
        @endcomponent
    @endslot
@endcomponent
