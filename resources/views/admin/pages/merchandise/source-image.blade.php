<div class="col-6 col-sm-4 col-xl-2">
    <article class="d-flex flex-column align-items-center text-center" aria-label="Printful mockup {{ $image->id }}">
        <img src="{{ $image->url }}" alt="{{ $image->alt }}" width="112" height="112" class="object-fit-contain rounded mb-2" loading="lazy">
        <a href="{{ route('admin.merchandise.images.download', ['merchandise' => $product, 'image' => $image->id] + $returnFilters) }}" class="btn btn-secondary btn-sm" aria-label="Download Printful mockup {{ $image->id }}">@icon('download')Download</a>
    </article>
</div>
