<section class="design-system__section" id="ds-spacing" aria-labelledby="ds-spacing-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">04 / RHYTHM</span><h2 id="ds-spacing-title">Spacing</h2><p>The shared five-step scale for padding, margins and gaps.</p></header>
  <div class="design-system__spacing">
    @foreach(range(1, 5) as $step)
      <div data-ds-sample data-ds-compact data-ds-label="Spacing {{ $step }}" data-ds-properties="width">
        <code>--space-{{ $step }}</code>
        <span class="design-system__space-bar" data-ds-space="{{ $step }}" data-ds-preview></span>
        <span data-ds-token="--space-{{ $step }}"></span>
        <dl class="design-system__specs design-system__specs--compact" data-ds-specs></dl>
        <code>.p-{{ $step }} / .m-{{ $step }} / .gap-{{ $step }}</code>
      </div>
    @endforeach
  </div>
</section>

<section class="design-system__section" id="ds-surfaces" aria-labelledby="ds-surfaces-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">05 / SHAPE & DEPTH</span><h2 id="ds-surfaces-title">Borders & shadows</h2><p>Corner radii and elevations used by containers, controls and overlays.</p></header>
  <div class="design-system__grid">
    @foreach(['radius' => 'Container', 'radius-sm' => 'Small container', 'radius-button' => 'Button', 'radius-input' => 'Input', 'radius-dropdown' => 'Dropdown', 'radius-pill' => 'Pill', 'radius-circle' => 'Circle'] as $token => $label)
      @component('admin.pages.settings.sample', ['label' => $label, 'usage' => '--'.$token, 'properties' => 'border,border-radius'])
        <div class="design-system__shape" data-ds-preview data-ds-radius="{{ $token }}">{{ $label }}</div>
      @endcomponent
    @endforeach
    @foreach(['shadow' => 'Standard', 'shadow-light' => 'Light', 'shadow-dark' => 'Dark', 'shadow-dropdown' => 'Dropdown'] as $token => $label)
      @component('admin.pages.settings.sample', ['label' => $label.' shadow', 'usage' => '--'.$token, 'properties' => 'box-shadow'])
        <div class="design-system__shape rounded-sm" data-ds-preview data-ds-shadow="{{ $token }}">{{ $label }}</div>
      @endcomponent
    @endforeach
    @foreach(['border' => 'Default border', 'border border-primary' => 'Brand border', 'border border-danger' => 'Error border'] as $class => $label)
      @component('admin.pages.settings.sample', ['label' => $label, 'usage' => '.'.str_replace(' ', ' .', $class), 'properties' => 'border'])
        <div class="design-system__shape rounded-sm {{ $class }}" data-ds-preview>{{ $label }}</div>
      @endcomponent
    @endforeach
  </div>
</section>
