<aside class="composer-contemporaries border rounded p-3 p-sm-4 {{ $composer->curiosity ? 'mt-4' : '' }}" aria-labelledby="composer-contemporaries-title">
    <a href="{{ route('webapp.composers.index') }}" class="link-none d-flex align-items-center justify-content-between gap-3 mb-3">
        <h2 id="composer-contemporaries-title" class="h5 mb-0">Contemporary composers</h2>
        @icon('chevron-right', ['mr' => 0, 'classes' => 'flex-shrink-0'])
    </a>
    <ul class="list-unstyled mb-0">
        @foreach($contemporaries as $contemporary)
        <li>
            <a href="{{ route('webapp.composers.show', $contemporary) }}" class="composer-contemporary link-none d-flex align-items-center gap-3 py-2">
                <img src="{{ $contemporary->cover_image }}" alt="" class="composer-contemporary-portrait rounded-circle flex-shrink-0" loading="lazy">
                <div class="composer-contemporary-copy flex-grow-1">
                    <h3 class="h6 mb-1">{{ $contemporary->name }}</h3>
                    <p class="small text-muted mb-0">
                        <span class="text-nowrap">{{ $contemporary->alive_on }}</span>
                        @if($contemporary->country)
                        <span class="mx-2" aria-hidden="true">·</span> {{ $contemporary->country->name }}
                        @endif
                    </p>
                </div>
                @icon('chevron-right', ['mr' => 0, 'classes' => 'flex-shrink-0'])
            </a>
        </li>
        @endforeach
    </ul>
</aside>
