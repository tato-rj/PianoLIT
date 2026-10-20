<span class="d-inline-flex align-items-center gap-2 text-nowrap">
    <img src="{{ optional($composer)->cover_image ?: asset('images/misc/placeholder-image.png') }}"
        alt="" width="28" height="28" loading="lazy" decoding="async"
        class="rounded-circle flex-shrink-0" style="width: 28px; height: 28px; object-fit: cover;"
        data-fallback="{{ asset('images/misc/placeholder-image.png') }}" onerror="this.onerror=null;this.src=this.dataset.fallback;">
    <span>{{ optional($composer)->name ?? 'Unknown composer' }}</span>
</span>
