<nav class="d-flex align-items-center gap-2 {{ $pathClasses ?? '' }}" aria-label="Explore path">
    <a class="d-lg-none badge bg-light text-muted rounded-sm border" href="{{ route('webapp.explore') }}">@icon('chevron-left', ['mr' => 0]) Explore</a>
    @foreach($breadcrumbs as $breadcrumb)
        <a class="badge bg-light text-muted rounded-sm border" href="{{ route('webapp.explore', $breadcrumb['params']) }}">@icon('chevron-left', ['mr' => 0]) {{ $breadcrumb['label'] }}</a>
    @endforeach
</nav>
