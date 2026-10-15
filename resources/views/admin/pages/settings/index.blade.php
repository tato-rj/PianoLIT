@extends('admin.layouts.app')

@section('head')
  @include('layouts.html.google.fonts')
@endsection

@section('content')

<div class="content-wrapper design-system" data-design-system>
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'icon' => 'settings',
      'title' => 'Design System',
      'subtitle' => 'A living reference for the PianoLIT interface.',
    ])

    <div class="design-system__intro">
      <div><span class="design-system__eyebrow">PIANOLIT / UI LIBRARY</span>
        <h2>One place for the details.</h2>
        <p>Explore the shared styles used on the website, web app and administration. Samples use the real components; measurements reflect this browser and update as you resize, hover or focus.</p>
      </div>
      <span class="design-system__live">@icon('circle-check')Live CSS values</span>
    </div>

    <nav class="design-system__nav" aria-label="Design system sections">
      @foreach(['typography' => 'Typography', 'colors' => 'Colors', 'spacing' => 'Spacing', 'surfaces' => 'Borders & shadows', 'buttons' => 'Buttons', 'inputs' => 'Inputs', 'components' => 'Components', 'tables' => 'Tables', 'icons' => 'Icons'] as $anchor => $label)
        <a href="#ds-{{ $anchor }}">{{ $label }}</a>
      @endforeach
    </nav>
    <noscript><p class="design-system__note">Enable JavaScript to see computed measurements. All visual samples remain available below.</p></noscript>

    @include('admin.pages.settings.typography')
    @include('admin.pages.settings.colors')
    @include('admin.pages.settings.surfaces')
    @include('admin.pages.settings.controls')
    @include('admin.pages.settings.components')

    <p class="design-system__note">Shared foundations: <code>resources/sass/_design-tokens.scss</code> and <code>resources/sass/design-system.scss</code>. Component-specific rules can override these defaults. This gallery uses the admin stylesheet; public-only examples are identified in their section.</p>
  </div>
</div>


@endsection

@section('scripts')
  <script src="{{ mix('js/views/admin-design-system.js') }}" defer></script>
@endsection
