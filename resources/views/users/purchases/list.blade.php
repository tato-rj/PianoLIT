@component('components.layout.accordion', ['id' => 'downloads-list'])
    @foreach($purchases as $purchase)
        @component('components.layout.accordion-item', ['id' => 'purchase-'.$purchase->id, 'parentId' => 'downloads-list'])
            @slot('title')
                <span class="d-block me-3 text-break">
                    <span class="d-block small text-muted mb-1">{{ $purchase->type === 'Infograph' ? 'Infographic' : $purchase->type }}</span>
                    <span class="d-block fw-bold mb-1">{{ $purchase->item ? $purchase->item->title : 'Resource unavailable' }}</span>
                    @if($purchase->created_at)<time class="d-block small text-muted fw-normal" datetime="{{ $purchase->created_at->toDateString() }}">{{ $purchase->created_at->format('M j, Y') }}</time>@endif
                </span>
            @endslot
            <div class="row g-3 align-items-center">
                <div class="col-sm-auto me-sm-auto">
                    <dl class="profile-facts mb-0"><dt>Cost</dt><dd class="mb-0">{{ $purchase->cost ? '$'.number_format($purchase->cost, 2) : 'Free' }}</dd></dl>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($purchase->item ? $purchase->item->links() : [] as $label => $hash)
                            <a class="btn btn-primary btn-sm" href="{{ route('shop.download', ['purchase' => $purchase, 'path' => $hash]) }}" aria-label="Download {{ $purchase->item->title }} ({{ $label }})">@icon('download')Download {{ $label }}</a>
                        @empty
                            <p class="small text-muted mb-0">This file is currently unavailable. <a href="{{ route('contact') }}">Contact us</a> for help.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endcomponent
    @endforeach
@endcomponent
