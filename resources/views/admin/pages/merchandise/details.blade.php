@component('admin.components.editor.section', ['id' => 'merchandise-details', 'sectionId' => 'product-details', 'title' => 'Product details', 'class' => 'mb-4'])
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
@endcomponent
