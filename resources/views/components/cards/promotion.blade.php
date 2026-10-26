<article class="promo-card pastel-card rounded p-4 mb-4" data-pastel-color="{{ $color ?? 'sky' }}" @if($preview ?? false) data-ds-preview @endif>
    <p class="mb-3"><span class="pastel-card__tag">{{ $category }}</span></p>
    @if(! empty($image))
        <a href="{{ $url }}" class="promo-card__image-link mb-3" tabindex="-1" aria-hidden="true">
            <img src="{{ $image }}" alt="" width="320" height="200" loading="lazy" decoding="async" class="promo-card__image {{ ($imageFit ?? 'cover') === 'contain' ? 'promo-card__image--contain' : '' }}">
        </a>
    @endif
    <h3 class="h6 mb-2"><a href="{{ $url }}" class="promo-card__title">{{ $title }}</a></h3>
    <p class="promo-card__description mb-3">{{ $description }}</p>
    <a href="{{ $url }}" class="btn pastel-card__action">{{ $action }} @icon('arrow-right', ['mr' => 0])<span class="visually-hidden">: {{ $title }}</span></a>
</article>
