<ul class="download-library-grid list-unstyled mb-4" aria-label="Your downloads">
    @foreach($purchases as $purchase)
        @php($resource = $library->card($purchase))
        <li class="download-library-item">
            <article class="download-card" aria-labelledby="download-title-{{ $purchase->id }}">
                <div class="download-card-cover" aria-hidden="true">
                    <span class="download-card-placeholder" @if($resource['image']) hidden @endif data-download-placeholder>@icon('file-text', ['mr' => 0])</span>
                    @if($resource['image'])<img src="{{ $resource['image'] }}" alt="" loading="lazy" decoding="async" data-download-cover>@endif
                </div>
                <div class="download-card-info">
                    <p class="text-muted mb-1">{{ $resource['type'] }}</p>
                    <h2 class="h6 mb-2 text-break" id="download-title-{{ $purchase->id }}">{{ $resource['title'] }}</h2>
                    <p class="download-card-meta text-muted mb-0">
                        @if($purchase->created_at)<time datetime="{{ $purchase->created_at->toDateString() }}">{{ $purchase->created_at->format('M j, Y') }}</time><span aria-hidden="true"> · </span>@endif
                        <span>{{ $purchase->cost ? '$'.number_format($purchase->cost, 2) : 'Free' }}</span>
                    </p>
                </div>
                <div class="download-card-actions">
                    @forelse($resource['links'] as $label => $hash)
                        <a class="btn btn-secondary" href="{{ route('shop.download', ['purchase' => $purchase, 'path' => $hash]) }}" aria-label="Download {{ $resource['title'] }} ({{ $label }})">@icon('download')<span class="download-card-action-label">Download </span>{{ $label }}</a>
                    @empty
                        <p class="text-muted mb-2">File unavailable</p>
                        <a href="{{ route('contact') }}">Get help @icon('arrow-right')</a>
                    @endforelse
                </div>
            </article>
        </li>
    @endforeach
</ul>
