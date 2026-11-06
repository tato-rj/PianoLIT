@component('components.dialogs.modal', ['id' => 'digital-purchases-modal', 'header' => 'Purchases', 'show' => true, 'options' => ['size' => 'lg']])
@slot('body')
    <p>{{ $purchaseProduct->title }}</p>
    @component('admin.components.table', [
        'id' => 'digital-purchases-table', 'label' => 'Purchases', 'items' => $purchases, 'pageSizes' => [],
        'columns' => ['Date', 'Account', 'Payment'], 'emptyTitle' => $purchases->total() ? 'No purchases on this page' : 'No purchases yet',
        'emptyDescription' => $purchases->total() ? 'Return to the first page to view purchases.' : 'Purchases will appear here when this product is downloaded.',
        'emptyAction' => $purchases->total() ? ['label' => 'First page', 'url' => $purchases->url(1)] : null,
    ])
        @foreach($purchases as $purchase)
        <tr>
            <td class="text-nowrap">{{ optional($purchase->created_at)->format('M j, Y') ?: 'Not set' }}</td>
            <td>
                @if($purchase->user)
                    <span class="admin-table__primary">{{ $purchase->user->full_name }}</span><small>ID {{ $purchase->user->id }}</small>
                @else<span class="text-muted">Account deleted</span>@endif
            </td>
            <td>{{ $purchase->cost ? 'Paid' : 'Free' }}</td>
        </tr>
        @endforeach
    @endcomponent
@endslot
@slot('footer')
    <a href="{{ route('admin.'.$kind.'.index', $returnFilters) }}" class="btn btn-secondary">Back to {{ $kind === 'ebooks' ? 'eBooks' : 'eScores' }}</a>
@endslot
@endcomponent
