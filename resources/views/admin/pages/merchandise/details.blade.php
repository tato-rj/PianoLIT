@component('admin.components.editor.section', ['id' => 'merchandise-details', 'sectionId' => 'product-details', 'title' => 'Product details', 'class' => 'mb-4'])
    @slot('actions')
        @if(app(\App\Services\Shop\Printful::class)->enabled() && (int) $product->printful_store_id !== 0)
            <form method="POST" action="{{ route('admin.merchandise.refresh', ['merchandise' => $product] + $returnFilters) }}">
                @csrf
                <input type="hidden" name="revision" value="{{ $product->revision }}">
                <button class="btn btn-secondary btn-sm" type="submit" aria-describedby="merchandise-refresh-help">@icon('refresh-cw')Refresh product from Printful</button>
            </form>
        @endif
    @endslot
    <p class="small text-muted mb-2">Printful #{{ $product->printful_product_id }} · Last refreshed {{ optional($product->synced_at)->format('M j, Y · g:ia') ?: 'Not recorded' }}</p>
    @if(app(\App\Services\Shop\Printful::class)->enabled() && (int) $product->printful_store_id !== 0)
        <p id="merchandise-refresh-help" class="small text-muted mb-3">Updates the Printful name, materials, options, prices and reference mockups. Your title edits, subtitle, description and uploaded images are kept.</p>
    @endif
    @if($errors->getBag('product_details')->has('revision'))
        @alert(['color' => 'red', 'message' => e($errors->getBag('product_details')->first('revision'))])
    @endif
    <form method="POST" action="{{ route('admin.merchandise.update', ['merchandise' => $product] + $returnFilters) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="revision" value="{{ $product->revision }}">
        <div class="row g-3">
            <div class="col-md-6">
                @input(['id' => 'merchandise-title', 'name' => 'name', 'label' => 'Title', 'value' => $product->name, 'limit' => 255, 'bag' => 'product_details', 'grid' => 'mb-2', 'describedBy' => 'merchandise-title-help', 'placeholder' => 'Product title'])
                <p id="merchandise-title-help" class="small text-muted mb-0">Title edits here are kept when refreshing from Printful.</p>
            </div>
            <div class="col-md-6">
                @input(['id' => 'merchandise-subtitle', 'name' => 'subtitle', 'label' => 'Subtitle', 'value' => $product->subtitle, 'limit' => 255, 'bag' => 'product_details', 'required' => false, 'grid' => 'mb-0', 'placeholder' => 'Optional subtitle'])
            </div>
            <div class="col-12">
                @textarea(['id' => 'merchandise-description', 'name' => 'description', 'label' => 'Description', 'value' => $product->description, 'limit' => 10000, 'rows' => 5, 'bag' => 'product_details', 'required' => false, 'placeholder' => 'Product description'])
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">@icon('check')Save details</button>
    </form>
    <div class="border-top mt-4 pt-3">
        <h3 class="h6 mb-2">Materials from Printful</h3>
        @forelse($materials as $material)
            <p class="{{ $loop->last ? 'mb-0' : 'mb-2' }}">@if($material['label'])<strong>{{ $material['label'] }}:</strong> @endif{{ $material['text'] }}</p>
        @empty
            <p class="text-muted mb-0">No material details saved from Printful.</p>
        @endforelse
    </div>
@endcomponent
