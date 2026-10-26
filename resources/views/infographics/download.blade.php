@auth
    @if($downloadPurchase)
        <a href="{{ $infograph->autoDownload() }}" class="btn btn-primary w-100" data-infographic-download>
            @icon('file-down')Download again
        </a>
        <p class="small text-muted mt-2 mb-0">First downloaded {{ $downloadPurchase->created_at->toFormattedDateString() }}</p>
    @else
        <form method="GET" action="{{ route('infographs.download', $infograph) }}" disable-on-submit>
            <button type="submit" class="btn btn-primary w-100" data-infographic-download>@icon('file-down')Download for free</button>
        </form>
    @endif
@else
    <a id="auth-only" href="{{ route('infographs.download', $infograph) }}" class="btn btn-primary w-100" data-infographic-download>
        @icon('file-down')Download for free
    </a>
    <p class="small text-muted mt-2 mb-0">Sign in or create an account to download this guide.</p>
@endauth
