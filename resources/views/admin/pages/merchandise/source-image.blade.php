<div class="col-6 col-sm-4 col-xl-2">
    <article class="d-flex flex-column align-items-center text-center" aria-label="Printful mockup {{ $image->id }}">
        <img src="{{ $image->url }}" alt="{{ $product->name }}" width="112" height="112" class="object-fit-contain rounded mb-2" loading="lazy">
        <p class="small mb-2 w-100 text-break">{{ $image->printful_colors ? implode(', ', $image->printful_colors) : 'Color unavailable' }}</p>
        <a href="{{ route('admin.merchandise.images.download', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}" class="btn btn-secondary btn-sm" aria-label="Download Printful mockup {{ $image->id }}">@icon('download')Download</a>
    </article>
</div>
