@php($hubCard = $hubCard ?? false)
<article class="promo-card pastel-card {{ $hubCard ? 'promo-card--hub' : 'rounded p-4 mb-4' }}" data-pastel-color="{{ $color ?? 'sky' }}" @if($preview ?? false) data-ds-preview @endif>
    @if($hubCard)
        <img src="{{ $image }}" alt="" width="720" height="960" loading="lazy" decoding="async" class="promo-card__artwork">
    @endif
    <div class="promo-card__details">
        <div class="promo-card__copy">
            <p class="mb-3"><span class="pastel-card__tag">{{ $category }}</span></p>
            @if(!$hubCard && !empty($image))
                <a href="{{ $url }}" class="promo-card__image-link mb-3" tabindex="-1" aria-hidden="true">
                    <img src="{{ $image }}" alt="" width="320" height="200" loading="lazy" decoding="async" class="promo-card__image {{ ($imageFit ?? 'cover') === 'contain' ? 'promo-card__image--contain' : '' }}">
                </a>
            @endif
            <h3 class="h6 mb-2"><a href="{{ $url }}" class="promo-card__title">{{ $title }}</a></h3>
            <p class="promo-card__description mb-3">{{ $description }}</p>
        </div>
    </div>
    <div class="promo-card__actions">
        <a href="{{ $url }}" class="btn pastel-card__action">
            <span class="promo-card__action-label">{{ $action }}</span>
            <span class="promo-card__compact-label">{{ $category }}</span>
            <span class="promo-card__arrow">@icon('arrow-right', ['mr' => 0])</span><span class="visually-hidden">: {{ $title }}</span>
        </a>
    </div>
</article>
