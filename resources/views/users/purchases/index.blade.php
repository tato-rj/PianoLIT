<section class="profile-section" aria-labelledby="downloads-heading">
    <h2 id="downloads-heading">My downloads</h2>
    <p class="text-muted">Your eBooks, eScores and infographics, ready to download again.</p>
    @if($purchases->total())
        @if($purchases->isNotEmpty())
            @paginationCount(['collection' => $purchases, 'align' => 'left', 'item' => 'resource'])
            <div class="profile-panel p-0 overflow-hidden mb-4">
                @include('users.purchases.list')
            </div>
        @else
            <p class="profile-panel">No resources on this page. <a href="{{ route('users.purchases') }}">Return to your library</a></p>
        @endif
        @pagination(['collection' => $purchases])
    @else
        @include('users.purchases.empty')
    @endif
</section>
