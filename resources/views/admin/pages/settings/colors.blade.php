<section class="design-system__section" id="ds-colors" aria-labelledby="ds-colors-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">02 / PALETTE</span><h2 id="ds-colors-title">Colors</h2><p>Brand, semantic and utility palettes, followed by the softer tones used in folders and feature cards.</p></header>
  @foreach([
    'Brand & interaction' => ['primary', 'primary-hover', 'primary-active', 'primary-disabled', 'primary-soft'],
    'Text & surfaces' => ['text', 'text-muted', 'text-dark', 'surface', 'surface-subtle', 'surface-dark', 'border', 'dropdown-border'],
    'Status' => ['success', 'danger', 'warning', 'info', 'secondary'],
    'Utility palette' => ['grey', 'red', 'orange', 'yellow', 'green', 'teal', 'blue', 'indigo', 'purple', 'pink'],
  ] as $group => $colors)
    <h3 class="design-system__group-title">{{ $group }}</h3>
    <div class="design-system__swatches">
      @foreach($colors as $color)
        <div class="design-system__swatch" data-ds-sample data-ds-compact data-ds-label="{{ ucwords(str_replace('-', ' ', $color)) }} color" data-ds-properties="background-color">
          <div class="design-system__color" data-ds-color-token="{{ $color }}" data-ds-color data-ds-preview></div>
          <strong>{{ ucwords(str_replace('-', ' ', $color)) }}</strong>
          <code>--color-{{ $color }}</code>
          <dl class="design-system__specs design-system__specs--compact" data-ds-specs></dl>
        </div>
      @endforeach
    </div>
  @endforeach
  <h3 class="design-system__group-title">Form palette</h3>
  <div class="design-system__swatches">
    @foreach(['background', 'border', 'text', 'muted', 'disabled-background'] as $color)
      <div class="design-system__swatch" data-ds-sample data-ds-compact data-ds-label="Form {{ str_replace('-', ' ', $color) }} color" data-ds-properties="background-color">
        <div class="design-system__color" data-ds-field-color="{{ $color }}" data-ds-color data-ds-preview></div>
        <strong>{{ ucwords(str_replace('-', ' ', $color)) }}</strong>
        <code>--field-{{ $color }}</code>
        <dl class="design-system__specs design-system__specs--compact" data-ds-specs></dl>
      </div>
    @endforeach
  </div>
  <h3 class="design-system__group-title">Repertoire levels</h3>
  <div class="design-system__swatches">
    @foreach(['elementary', 'beginner', 'intermediate', 'advanced'] as $level)
      <div class="design-system__swatch" data-ds-sample data-ds-compact data-ds-label="{{ ucfirst($level) }} color" data-ds-properties="background-color">
        <div class="design-system__color bg-{{ $level }}" data-ds-color data-ds-preview></div>
        <strong>{{ ucfirst($level) }}</strong>
        <code>.bg-{{ $level }}</code>
        <dl class="design-system__specs design-system__specs--compact" data-ds-specs></dl>
      </div>
    @endforeach
  </div>
  <h3 class="design-system__group-title">Pastel palette</h3>
  <div class="design-system__swatches">
    @foreach(['sky', 'cream', 'mint', 'lavender', 'peach', 'rose', 'aqua', 'butter', 'sage', 'lilac', 'periwinkle', 'terracotta'] as $tone)
      <div class="design-system__swatch" data-ds-sample data-ds-compact data-ds-label="{{ ucfirst($tone) }} pastel color" data-ds-properties="background-color">
        <div class="design-system__color" data-ds-pastel="{{ $tone }}" data-ds-color data-ds-preview></div>
        <strong>{{ ucfirst($tone) }}</strong>
        <code>--pastel-{{ $tone }}-accent</code>
        <dl class="design-system__specs design-system__specs--compact" data-ds-specs></dl>
      </div>
    @endforeach
  </div>
  <p class="design-system__note">Each pastel also defines surface, border, ink, action and hover tokens. Brand primary and utility blue are separate colors.</p>
</section>
