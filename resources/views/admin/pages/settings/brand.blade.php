@php
  $brandExports = [
    ['label' => 'iOS app icon', 'usage' => '1024 × 1024 PNG · square · no transparency',
      'images' => [['images/brand/app-icon-ios-1024.png', 'PianoLIT iOS app icon', 128]],
      'downloads' => ['images/brand/app-icon-ios-1024.png' => 'Download 1024 × 1024']],
    ['label' => 'Rounded & QR artwork', 'usage' => '1200 × 1200 PNG · transparent corners',
      'images' => [['images/brand/icon-rounded.png', 'Rounded PianoLIT artwork', 96], ['images/brand/icon-rounded-margin.png', 'PianoLIT QR artwork with a white inset', 96]],
      'downloads' => ['images/brand/icon-rounded.png' => 'Rounded PNG', 'images/brand/icon-rounded-margin.png' => 'QR PNG']],
  ];
  foreach (['' => 'Website & web app', 'admin-' => 'Administration'] as $prefix => $label) {
    $brandExports[] = ['label' => $label.' favicons', 'usage' => 'PNG · ICO · Safari mask',
      'images' => [[$prefix.'favicon-16x16.png', $label.' favicon at 16 pixels', 16], [$prefix.'favicon-32x32.png', $label.' favicon at 32 pixels', 32], [$prefix.'safari-pinned-tab.svg', $label.' monochrome Safari pinned-tab icon', 48]],
      'downloads' => [$prefix.'favicon-16x16.png' => '16 px PNG', $prefix.'favicon-32x32.png' => '32 px PNG', $prefix.'favicon.ico' => 'ICO', $prefix.'safari-pinned-tab.svg' => 'Safari SVG']];
    $brandExports[] = ['label' => $label.' home-screen icons', 'usage' => 'Square PNG · no transparency',
      'images' => [[$prefix.'apple-touch-icon.png', $label.' Apple home-screen icon', 80], [$prefix.'android-chrome-512x512.png', $label.' Android home-screen icon', 96]],
      'downloads' => [$prefix.'apple-touch-icon.png' => 'Apple · 180 px', $prefix.'android-chrome-192x192.png' => 'Android · 192 px', $prefix.'android-chrome-512x512.png' => 'Android · 512 px', $prefix.'mstile-150x150.png' => 'Windows · 150 px']];
  }
@endphp
<section class="design-system__section" id="ds-brand" aria-labelledby="ds-brand-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">01 / BRAND</span><h2 id="ds-brand-title">PianoLIT icons</h2><p>The approved icons for the website, web app, administration and app installs. Download the version you need below.</p></header>
  <div class="design-system__grid design-system__grid--wide">
    @foreach([false => 'Website & web app', true => 'Administration'] as $admin => $label)
      @php($variantPath = 'images/brand/'.($admin ? 'admin' : 'app').'-icon')
      @component('admin.pages.settings.sample', ['label' => $label, 'usage' => 'SVG · PNG', 'properties' => 'width,height'])
        <div class="design-system__brand-preview" data-ds-preview>
          @brandIcon(['admin' => (bool) $admin, 'size' => '128px'])
        </div>
        <div class="design-system__row">
          <a class="btn btn-secondary btn-sm" href="{{ asset(mix($variantPath.'.svg')) }}" download>@icon('download')SVG</a>
          <a class="btn btn-secondary btn-sm" href="{{ asset(mix($variantPath.'.png')) }}" download>@icon('download')PNG</a>
        </div>
      @endcomponent
    @endforeach
  </div>
  <h3 class="design-system__group-title">App, browser & install exports</h3>
  <div class="design-system__grid design-system__grid--wide">
    @foreach($brandExports as $export)
      @component('admin.pages.settings.sample', ['label' => $export['label'], 'usage' => $export['usage'], 'properties' => 'width,height'])
        <div class="design-system__brand-preview" data-ds-preview>
          @foreach($export['images'] as [$file, $alt, $size])
            <img src="{{ asset(mix($file)) }}" width="{{ $size }}" height="{{ $size }}" alt="{{ $alt }}">
          @endforeach
        </div>
        <div class="design-system__row">
          @foreach($export['downloads'] as $file => $format)
            <a class="btn btn-secondary btn-sm" href="{{ asset(mix($file)) }}" download>@icon('download'){{ $format }}</a>
          @endforeach
        </div>
      @endcomponent
    @endforeach
  </div>
</section>
