@php
  $brandVariants = [];
  $splashProfileCount = count(json_decode(file_get_contents(resource_path('data/brand-splash-screens.json')), true));
  foreach ([false => 'Website & web app', true => 'Administration'] as $admin => $label) {
    $splashVariant = $admin ? 'admin' : 'app';
    $variantPath = 'images/brand/'.($admin ? 'admin' : 'app').'-icon';
    $prefix = $admin ? 'admin-' : '';
    $artwork = [
      [$variantPath.'.svg', 'Scalable icon', 'SVG', '512 × 512 viewBox', 'Scalable vector · opaque background · square canvas'],
      [$variantPath.'.png', 'Raster icon', 'PNG', $admin ? '512 × 512 px' : '1238 × 1200 px', 'RGB · no transparency · square corners'],
    ];
    if (!$admin) {
      $artwork[] = ['images/brand/app-icon-ios-1024.png', 'iOS app icon', 'PNG', '1024 × 1024 px', 'RGB · no transparency · square corners'];
      $artwork[] = ['images/brand/icon-rounded.png', 'Rounded artwork', 'PNG', '1200 × 1200 px', 'Rounded corners · transparent background'];
      $artwork[] = ['images/brand/icon-rounded-margin.png', 'QR artwork', 'PNG', '1200 × 1200 px', 'White inset · rounded corners · transparent background'];
    }
    $brandVariants[] = ['admin' => (bool) $admin, 'label' => $label, 'modalId' => 'ds-brand-'.($admin ? 'admin' : 'user').'-downloads', 'groups' => [
      'Artwork' => $artwork,
      'Splash screens' => [
        ['images/brand/'.$splashVariant.'-splash.svg', 'Splash artwork', 'SVG', '390 × 844 viewBox', 'Scalable portrait artwork · centered icon · brand background'],
        ['images/brand/splash/'.$splashVariant.'-1170x2532.png', 'Phone splash preview', 'PNG', '1170 × 2532 px', 'RGB · no transparency · portrait'],
        ['images/brand/'.$splashVariant.'-splash-screens.zip', 'All iPhone & iPad splash sizes', 'ZIP', ($splashProfileCount * 2).' PNG images', $splashProfileCount.' screen profiles · portrait and landscape · full dimensions in the included README'],
      ],
      'Browser tabs' => [
        [$prefix.'favicon-16x16.png', 'Small favicon', 'PNG', '16 × 16 px', 'Rounded corners · transparency'],
        [$prefix.'favicon-32x32.png', 'Large favicon', 'PNG', '32 × 32 px', 'Rounded corners · transparency'],
        [$prefix.'favicon.ico', 'Multi-size favicon', 'ICO', '16 × 16, 32 × 32 and 48 × 48 px', 'Three embedded sizes · rounded corners · transparency'],
        [$prefix.'safari-pinned-tab.svg', 'Safari pinned tab', 'SVG', '512 × 512 viewBox', 'Scalable monochrome mask · transparent background'],
      ],
      'Home screen & Windows' => [
        [$prefix.'apple-touch-icon.png', 'Apple home screen', 'PNG', '180 × 180 px', 'RGB · no transparency · platform applies the corner mask'],
        [$prefix.'android-chrome-192x192.png', 'Android home screen · small', 'PNG', '192 × 192 px', 'RGB · no transparency · platform applies the corner mask'],
        [$prefix.'android-chrome-512x512.png', 'Android home screen · large', 'PNG', '512 × 512 px', 'RGB · no transparency · platform applies the corner mask'],
        [$prefix.'mstile-150x150.png', 'Windows tile', 'PNG', '150 × 150 px', 'RGB · no transparency · square corners'],
      ],
    ]];
  }
@endphp
<section class="design-system__section" id="ds-brand" aria-labelledby="ds-brand-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">01 / BRAND</span><h2 id="ds-brand-title">PianoLIT icons</h2><p>The approved icons for the website, web app and administration. Choose Download to see every format, size and specification.</p></header>
  <div class="design-system__grid design-system__grid--wide">
    @foreach($brandVariants as $variant)
      @component('admin.pages.settings.sample', ['label' => $variant['label'], 'editable' => false])
        <div class="design-system__brand-preview">
          @brandIcon(['admin' => $variant['admin'], 'size' => '128px'])
        </div>
        <div class="text-center mt-3">
          <button class="btn btn-secondary" type="button" data-bs-toggle="modal" data-bs-target="#{{ $variant['modalId'] }}" aria-controls="{{ $variant['modalId'] }}" aria-haspopup="dialog">@icon('download')Download</button>
        </div>
      @endcomponent
    @endforeach
  </div>
  @foreach($brandVariants as $variant)
    @component('components.modal', ['id' => $variant['modalId'], 'header' => e($variant['label']).' icon downloads', 'descriptionId' => $variant['modalId'].'-description', 'centered' => true, 'options' => ['size' => 'lg']])
      @slot('body')
        <p id="{{ $variant['modalId'] }}-description" class="text-muted">Choose a version below. Dimensions describe the actual download file.</p>
        @foreach($variant['groups'] as $group => $downloads)
          <h3 class="h6 mt-4 mb-2">{{ $group }}</h3>
          <ul class="list-group list-group-flush">
            @foreach($downloads as [$file, $label, $format, $dimensions, $specifications])
              <li class="list-group-item px-0 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                  <strong class="d-block">{{ $label }}</strong>
                  <span class="d-block">{{ $format }} · {{ $dimensions }}</span>
                  <small class="text-muted">{{ $specifications }}</small>
                </div>
                <a class="btn btn-secondary btn-sm" href="{{ asset(mix($file)) }}" download aria-label="Download {{ $label }} for {{ $variant['label'] }}">@icon('download')Download</a>
              </li>
            @endforeach
          </ul>
        @endforeach
      @endslot
    @endcomponent
  @endforeach
</section>
