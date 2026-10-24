@extends('admin.layouts.app')

@section('head')
  @include('layouts.html.google.fonts')
  @include('components.form.number-fonts')
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
        <p>Explore the shared styles used on the website, web app and administration. Edit any CSS value to preview a variation on that sample. Changes stay on this page and disappear when you reload.</p>
      </div>
      <div class="design-system__tools">
        <span class="design-system__live">@icon('pencil')Live CSS playground</span>
        <button class="design-system__reset" type="button" data-ds-reset-all disabled>Reset all previews</button>
      </div>
    </div>

    <nav class="design-system__nav" aria-label="Design system sections">
      @foreach(['brand' => 'PianoLIT icons', 'typography' => 'Typography', 'colors' => 'Colors', 'spacing' => 'Spacing', 'surfaces' => 'Borders & shadows', 'buttons' => 'Buttons', 'inputs' => 'Inputs', 'components' => 'Components', 'tables' => 'Tables', 'icons' => 'Icons'] as $anchor => $label)
        <a href="#ds-{{ $anchor }}">{{ $label }}</a>
      @endforeach
    </nav>
    <noscript><p class="design-system__note">Enable JavaScript to see and edit computed CSS values. All visual samples remain available below.</p></noscript>

    @include('admin.pages.settings.brand')
    @include('admin.pages.settings.typography')
    @include('admin.pages.settings.colors')
    @include('admin.pages.settings.surfaces')
    @include('admin.pages.settings.controls')
    @include('admin.pages.settings.components')

    <p class="design-system__note">Shared defaults: <code>resources/sass/shared/_tokens.scss</code>. Shared styles: <code>resources/sass/shared/</code>. Reusable components: <code>resources/sass/components/</code>. This gallery uses the admin stylesheet; public-only examples are identified in their section.</p>
  </div>
</div>


@endsection

@section('scripts')
  @stack('scripts')
  <script src="{{ asset('js/components/admin-table-bulk-delete.js') }}" defer></script>
  <script src="{{ mix('js/views/admin-design-system.js') }}" defer></script>
  <script src="{{ mix('js/views/admin-access-limits.js') }}" defer></script>
@endsection
