<section class="design-system__section" id="ds-typography" aria-labelledby="ds-typography-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">02 / FOUNDATIONS</span><h2 id="ds-typography-title">Typography</h2><p>System fonts for everyday reading, Poppins for headings, and a serif stack for editorial accents.</p></header>
  <div class="design-system__grid">
    @foreach(['body' => ['System sans', '--font-family-body'], 'heading' => ['Poppins', '--font-family-heading'], 'serif' => ['Editorial serif', '--font-family-serif']] as $family => $font)
      @component('admin.pages.settings.sample', ['label' => $font[0], 'usage' => $font[1], 'properties' => 'font-family,font-size,font-weight,line-height,letter-spacing'])
        <p class="design-system__font-preview" data-ds-font="{{ $family }}" data-ds-preview>Music begins with curiosity.</p>
      @endcomponent
    @endforeach
  </div>
  <div class="design-system__type-scale">
    @foreach(['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'base', 'sm', 'xs', 'tiny'] as $size)
      @component('admin.pages.settings.sample', ['label' => strtoupper($size), 'usage' => '--font-size-'.$size, 'properties' => 'font-size,line-height,font-weight,letter-spacing'])
        <p class="design-system__type-preview" data-ds-type="{{ $size }}" data-ds-preview>A world of piano music</p>
      @endcomponent
    @endforeach
  </div>
  <p class="design-system__note">Heading elements use the H1–H6 tokens above. The legacy <code>.h1</code> utility uses <code>--font-size-h1-utility</code> (<span data-ds-token="--font-size-h1-utility"></span>). Body line height: <span data-ds-token="--line-height-body"></span>; heading line height: <span data-ds-token="--line-height-heading"></span>.</p>
  <div class="design-system__row">
    @foreach([400 => 'Regular', 600 => 'Semibold', 800 => 'Extra bold'] as $weight => $label)
      <span class="design-system__font-weight" data-ds-font-weight="{{ $weight }}">{{ $label }} / {{ $weight }}</span>
    @endforeach
  </div>
</section>
