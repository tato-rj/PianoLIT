@php
    $product = $purchase->item;
    $catalogLabel = $product instanceof \App\Shop\eScore ? 'eScores' : 'eBooks';
    $noCharge = ! $purchase->cost && ! $purchase->charge_id;
@endphp
<section class="container shop-checkout py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Shop', 'url' => route('shop.home')],
        ['label' => $catalogLabel, 'url' => $product->indexRoute()],
        ['label' => rtrim($product->title, '. '), 'url' => $product->showRoute()],
        ['label' => 'Download'],
    ]])
    <header class="mb-4 mb-lg-5">
        <p class="text-success small mb-2">@icon('circle-check'){{ $noCharge ? 'Added to your downloads' : 'Order confirmed' }}</p>
        <h1 class="h2 mb-2">Your download is ready</h1>
        <p class="text-muted mb-0">Thanks{{ auth()->user()->first_name ? ', '.auth()->user()->first_name : '' }}. Choose a file below and enjoy the music.</p>
    </header>
    <div class="row g-4 g-lg-5 align-items-start">
        <div class="col-lg-7">
            <section class="mb-4" aria-labelledby="shop-files-title">
                <h2 class="h5 mb-3" id="shop-files-title">Download your files</h2>
                @foreach($product->links() as $label => $hash)
                <div class="shop-download-file d-flex align-items-center justify-content-between gap-3 py-3 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        @icon('file-text', ['size' => 'lg', 'mr' => 0, 'color' => 'primary'])
                        <div><span class="d-block fw-bold">{{ $label }}</span><span class="small text-muted">{{ strtoupper($label) === 'PDF' ? 'Read or print your edition' : 'Read in your eBook app' }}</span></div>
                    </div>
                    <a href="{{ route('shop.download', ['purchase' => $purchase, 'path' => $hash]) }}" class="btn btn-{{ $loop->first ? 'primary' : 'secondary' }} flex-shrink-0" target="_blank" rel="noopener noreferrer" aria-label="Download {{ $label }}">@icon('download')Download</a>
                </div>
                @endforeach
            </section>
            <div class="d-flex align-items-start gap-3 mb-4">
                @icon('folder-open', ['mr' => 0, 'color' => 'grey'])
                <div><h2 class="h6 mb-1">Keep it in your library</h2><p class="text-muted small mb-2">Your files are saved to your account. Come back to My downloads whenever you need another copy.</p><a href="{{ route('users.purchases') }}">Go to My downloads @icon('arrow-right', ['mr' => 0])</a></div>
            </div>
            <a class="btn btn-secondary" href="{{ $product->indexRoute() }}">@icon('arrow-left')Browse {{ $catalogLabel }}</a>
        </div>
        <aside class="col-lg-5" aria-label="Order details">
            <div class="shop-order-summary p-4">
                <h2 class="h5 mb-4">Order details</h2>
                @include('shop.components.order-item')
                <dl class="shop-order-summary__totals mt-4 mb-0">
                    <div class="d-flex justify-content-between gap-3 mb-2"><dt class="fw-normal text-muted">Order</dt><dd class="mb-0">#{{ $purchase->id }}</dd></div>
                    <div class="d-flex justify-content-between gap-3 mb-2"><dt class="fw-normal text-muted">Date</dt><dd class="mb-0">{{ $purchase->created_at->toFormattedDateString() }}</dd></div>
                    <div class="d-flex justify-content-between align-items-baseline gap-3 border-top pt-3 mt-3"><dt>Total paid <span class="fw-normal small text-muted">USD</span></dt><dd class="h5 mb-0">{{ $purchase->cost !== null || $noCharge ? '$'.number_format($purchase->cost ?? 0, 2) : 'Not recorded' }}</dd></div>
                </dl>
                @if($noCharge)<p class="small text-muted mt-3 mb-0">No payment was required for this download.</p>@endif
            </div>
            <p class="small text-muted mt-3 mb-0">@icon('circle-help')Trouble with your files? <a href="mailto:{{ config('app.emails.general') }}?subject=Help%20with%20order%20%23{{ $purchase->id }}">Contact us</a></p>
        </aside>
    </div>
</section>
