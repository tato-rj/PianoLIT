@php($displayMockupUrl = $mockupProduct->displayMockupUrl())
@component('admin.components.editor.section', ['id' => 'display-mockup', 'sectionId' => 'display-mockup', 'title' => 'Display mockup', 'description' => 'A styled product image for Best deals and Shop displays. Your regular product images stay separate.'])
    @if($displayMockupUrl)
        <img src="{{ $displayMockupUrl }}" alt="Current display mockup" class="d-block w-100 object-fit-contain rounded mb-3" style="max-height: 240px" loading="lazy">
    @endif
    @if($errors->getBag('displayMockup')->any())
        @alert(['color' => 'red', 'message' => e($errors->getBag('displayMockup')->first())])
    @endif
    <form method="POST" action="{{ $mockupAction }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="action" value="upload">
        <input type="hidden" name="mockup_version" value="{{ \App\Services\Shop\DisplayMockups::version($mockupProduct) }}">
        @if($mockupProduct instanceof \App\Merchandise\MerchandiseProduct)<input type="hidden" name="revision" value="{{ $mockupProduct->revision }}">@endif
        @include('components.forms.upload.file', ['id' => 'display-mockup-file', 'name' => 'display_mockup', 'label' => $displayMockupUrl ? 'Replace display mockup' : 'Choose display mockup', 'accept' => 'image/jpeg,image/png,image/webp', 'required' => true, 'bag' => 'displayMockup'])
        <p class="small text-muted">JPG, PNG or WebP · up to 10 MB, 6,000 pixels per side and 24 megapixels. Keep the product near the center and leave a light, uncluttered area at the bottom for the card text.</p>
        <button class="btn btn-primary" type="submit">@icon('upload')Save display mockup</button>
    </form>
    @if($displayMockupUrl)
        <form method="POST" action="{{ $mockupAction }}" class="mt-3">
            @csrf
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="mockup_version" value="{{ \App\Services\Shop\DisplayMockups::version($mockupProduct) }}">
            @if($mockupProduct instanceof \App\Merchandise\MerchandiseProduct)<input type="hidden" name="revision" value="{{ $mockupProduct->revision }}">@endif
            <button class="btn btn-secondary btn-sm" type="submit">@icon('trash-2')Remove display mockup</button>
        </form>
    @else
        <p class="small text-muted mt-3 mb-0">Until you add a display mockup, Shop uses the regular product image.</p>
    @endif
@endcomponent
