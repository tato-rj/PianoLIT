<section class="design-system__section" id="ds-components" aria-labelledby="ds-components-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">08 / BUILDING BLOCKS</span><h2 id="ds-components-title">Components</h2><p>Common combinations for content, feedback and navigation.</p></header>
  <div class="design-system__grid design-system__grid--wide">
    @component('admin.pages.settings.sample', ['label' => 'Editor section', 'usage' => 'admin.components.editor.section', 'properties' => 'padding,background-color,border,border-radius'])
      @component('admin.components.editor.section', ['id' => 'ds-editor-section', 'title' => 'Piece details', 'description' => 'Related fields grouped in one section.', 'preview' => true])
        @input(['bag' => 'default', 'id' => 'ds-editor-name', 'name' => 'ds_editor_name', 'label' => 'Name', 'value' => 'Sonatina', 'required' => false])
      @endcomponent
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Page loading', 'usage' => 'admin.components.page-loading', 'properties' => 'height,background-color'])
      @include('admin.components.page-loading', ['preview' => true])
      <p class="text-muted mt-3 mb-0">The top bar advances while an admin page opens, then completes and fades after loading.</p>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Content card', 'usage' => '.card .rounded .shadow-light', 'properties' => 'padding,border,border-radius,box-shadow'])
      <div class="card rounded shadow-light" data-ds-preview><div class="card-body"><h3 class="h5">Discover your next piece</h3><p>Thoughtfully chosen repertoire for every stage of your piano journey.</p><button type="button" class="btn btn-primary btn-sm">Explore pieces</button></div></div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Signup card', 'usage' => 'components.signup-card', 'properties' => 'padding,color,background-color,border,border-radius,box-shadow'])
      @include('components.signup-card', app(\App\Services\WebApp\AccessPrompts::class)->prompt('favorites') + [
        'headingId' => 'ds-signup-card-title', 'preview' => true, 'actionUrl' => '#',
      ])
    @endcomponent
    @foreach(['signup' => 'Free signup modal', 'premium' => 'Premium modal'] as $kind => $label)
      @component('admin.pages.settings.sample', ['label' => $label, 'usage' => 'components.access-modal', 'properties' => 'padding,color,background-color,border-radius'])
        <button type="button" class="btn btn-secondary" data-ds-preview data-bs-toggle="modal" data-bs-target="#ds-{{ $kind }}-prompt">Open {{ $label }}</button>
      @endcomponent
    @endforeach
    @component('admin.pages.settings.sample', ['label' => 'Membership plan card', 'usage' => 'components.membership-plan-card', 'properties' => 'padding,background-color,border-radius,box-shadow'])
      @component('components.membership-plan-card', ['plan' => (object) ['name' => 'yearly', 'price' => 8999], 'monthlyPlan' => (object) ['name' => 'monthly', 'price' => 999], 'headingId' => 'ds-membership-plan', 'preview' => true])
        @slot('action')<button type="button" class="btn btn-primary">Choose Yearly @icon('arrow-right', ['mr' => 0, 'ml' => 2])</button>@endslot
      @endcomponent
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Saved payment card', 'usage' => 'components.payment-card-preview · saved', 'properties' => 'padding,color,background-color,border-radius,box-shadow'])
      @include('components.payment-card-preview', ['saved' => ['brand' => 'visa', 'last4' => '4242'], 'selectable' => true, 'preview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Add payment card', 'usage' => 'components.payment-card-preview · add', 'properties' => 'padding,color,background-color,border,border-radius'])
      @include('components.payment-card-preview', ['add' => true, 'modalId' => 'ds-card-editor-example', 'preview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Segmented selection', 'usage' => 'components.segmented-control', 'properties' => 'font-size,padding,color,border,border-radius'])
      @include('components.segmented-control', ['id' => 'ds-beat-count', 'name' => 'ds-beat-count', 'label' => 'Sample beat count', 'options' => [1 => '1', 2 => '2', 3 => '3', 4 => '4'], 'value' => 1, 'preview' => true])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Segmented tabs', 'usage' => 'components.tab-navigation · variant: segmented', 'properties' => 'font-size,padding,color,border,border-radius'])
      @component('components.tab-navigation', ['id' => 'ds-account-tabs', 'label' => 'Sample account sections', 'variant' => 'segmented', 'preview' => true, 'items' => [
        ['target' => 'ds-account-overview', 'label' => 'Overview'], ['target' => 'ds-account-billing', 'label' => 'Billing'],
      ]])
      <div class="tab-content pt-3"><div class="tab-pane fade show active" id="ds-account-overview" role="tabpanel" aria-labelledby="ds-account-tabs-ds-account-overview" tabindex="0">Your plan and renewal details.</div><div class="tab-pane fade" id="ds-account-billing" role="tabpanel" aria-labelledby="ds-account-tabs-ds-account-billing" tabindex="0">Your payment method and billing preferences.</div></div>
      @endcomponent
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Pastel feature card · website / web app', 'usage' => '.pastel-card [data-pastel-color=mint]', 'properties' => 'color,background-color,border,border-radius'])
      <div class="pastel-card rounded p-4" data-pastel-color="mint" data-ds-preview><span class="pastel-card__tag">YOUR COLLECTION</span><h3 class="h5 mt-3">A little music, every day.</h3><p>A softer surface for folders and featured content.</p><button type="button" class="btn pastel-card__action">Open collection @icon('arrow-right', ['mr' => 0])</button></div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Status badges', 'usage' => '.badge .rounded-pill .alert-{color}', 'properties' => 'font-size,padding,color,background-color,border-radius'])
      <div class="design-system__row"><span class="badge rounded-pill alert-green" data-ds-preview>Active</span><span class="badge rounded-pill alert-blue">Trial</span><span class="badge rounded-pill alert-orange">Pending</span><span class="badge rounded-pill alert-red">Ended</span></div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Inline feedback', 'usage' => '.alert .alert-green .rounded', 'properties' => 'padding,color,background-color,border-radius'])
      <div class="alert alert-green rounded" data-ds-preview>@icon('circle-check')Your changes have been saved.</div><div class="alert alert-red rounded mb-0">@icon('circle-alert')Please check the highlighted fields.</div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Tabs', 'usage' => '.nav .nav-tabs + .tab-content', 'properties' => 'font-size,padding,color,border'])
      <ul class="nav nav-tabs" role="tablist" aria-label="Sample piece tabs">
        <li class="nav-item" role="presentation"><button class="nav-link active" id="ds-about-tab" data-bs-toggle="tab" data-bs-target="#ds-about-panel" type="button" role="tab" aria-controls="ds-about-panel" aria-selected="true" data-ds-preview>About</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" id="ds-score-tab" data-bs-toggle="tab" data-bs-target="#ds-score-panel" type="button" role="tab" aria-controls="ds-score-panel" aria-selected="false" tabindex="-1">Score</button></li>
      </ul>
      <div class="tab-content pt-3"><div class="tab-pane fade show active" id="ds-about-panel" role="tabpanel" aria-labelledby="ds-about-tab" tabindex="0">Background, listening and practice notes.</div><div class="tab-pane fade" id="ds-score-panel" role="tabpanel" aria-labelledby="ds-score-tab" tabindex="0">The score appears in this panel.</div></div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Dropdown', 'usage' => '.dropdown-menu + .dropdown-item', 'properties' => 'padding,border,border-radius,box-shadow'])
      <div class="dropdown-menu show design-system__static-menu" data-ds-preview aria-label="Sample menu"><span class="dropdown-header">Collection actions</span><button type="button" class="dropdown-item">@icon('pencil')Rename collection</button><button type="button" class="dropdown-item">@icon('copy')Duplicate collection</button><div class="dropdown-divider"></div><button type="button" class="dropdown-item text-danger">@icon('trash')Remove collection</button></div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Dialog', 'usage' => '.modal + .modal-content', 'properties' => 'padding,border,border-radius,box-shadow'])
      <div class="modal design-system__modal-preview"><div class="modal-dialog m-0"><div class="modal-content" data-ds-preview><div class="modal-body"><h3 class="h5">A moment of focus</h3><p>Dialogs keep a single task in view.</p><button class="btn btn-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#ds-example-modal">Open sample dialog</button></div></div></div></div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Breadcrumbs', 'usage' => 'admin.components.breadcrumb · page.title breadcrumbs', 'properties' => 'font-size,color,line-height'])
      @include('admin.components.breadcrumb', ['designSystemPreview' => true, 'items' => [['label' => 'Overview', 'url' => '#ds-components'], ['label' => 'Email lists', 'url' => '#ds-components'], ['label' => 'Free Pick']]])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Links', 'usage' => 'a + .text-muted', 'properties' => 'font-size,color,text-decoration'])
      <p><a href="#ds-typography" data-ds-preview>Explore typography @icon('arrow-right', ['mr' => 0])</a></p><p class="text-muted mb-0">Supporting text gives the primary action room to breathe.</p>
    @endcomponent
  </div>
</section>

<section class="design-system__section" id="ds-tables" aria-labelledby="ds-tables-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">09 / ADMINISTRATION</span><h2 id="ds-tables-title">Tables</h2><p>The reusable admin table, shown with fictional data. Directory pages use 12 results per page by default.</p><code>admin.components.table · resources/sass/admin/_tables.scss</code></header>
  <div data-ds-sample data-ds-label="Table" data-ds-properties="padding,background-color,border,border-radius">
    <div data-ds-preview>
      @component('admin.components.table', ['id' => 'ds-example-table', 'label' => 'Design system sample accounts', 'title' => 'Membership directory', 'description' => 'Sample content', 'items' => collect(['member', 'trial', 'ended']), 'columns' => [['label' => 'Select', 'hidden' => true], 'Account', 'Plan', 'Status']])
        @slot('toolbar')
          @include('admin.components.table-bulk-delete', ['id' => 'ds-table-selection', 'tableId' => 'ds-example-table', 'action' => '#ds-tables', 'label' => 'sample subscribers', 'preview' => true])
        @endslot
        <tr><td><input type="checkbox" class="form-check-input" name="ids[]" value="1" form="ds-table-selection-form" aria-label="Select Sample member"></td><td><span class="admin-table__primary">Sample member</span><small>member@example.com</small></td><td>Annual</td><td><span class="admin-table__badge admin-table__badge--green">Active</span></td></tr>
        <tr><td><input type="checkbox" class="form-check-input" name="ids[]" value="2" form="ds-table-selection-form" aria-label="Select Sample trial"></td><td><span class="admin-table__primary">Sample trial</span><small>trial@example.com</small></td><td>Monthly</td><td><span class="admin-table__badge admin-table__badge--blue">Trial</span></td></tr>
        <tr><td><input type="checkbox" class="form-check-input" name="ids[]" value="3" form="ds-table-selection-form" aria-label="Select Access selection"></td><td><span class="admin-table__primary">Access selection</span><small>Native status menu</small></td><td>Reusable control</td><td>@include('components.status-select', ['name' => 'ds-access-status', 'value' => 'limited', 'label' => 'Sample access status', 'options' => ['available', 'limited', 'blocked']])</td></tr>
        <tr><td><input type="checkbox" class="form-check-input" name="ids[]" value="4" form="ds-table-selection-form" aria-label="Select Account required"></td><td><span class="admin-table__primary">Account required</span><small>Guest account features</small></td><td>Locked control</td><td>@include('components.status-select', ['name' => 'ds-account-required', 'value' => 'blocked', 'label' => 'Sample account-required status', 'selectedLabel' => 'Account required', 'tone' => 'muted', 'options' => ['available', 'blocked'], 'disabled' => true])</td></tr>
      @endcomponent
      @component('admin.components.table-save-actions', ['status' => 'Unsaved changes'])
        <button type="button" class="btn btn-primary">@icon('check')Save changes</button>
      @endcomponent
    </div>
    <dl class="design-system__specs" data-ds-specs></dl>
  </div>
  <p class="design-system__note">Table card values are measured from the shared component. Filters and pagination use the same styles on live directory pages.</p>
  <div class="design-system__grid">
    @component('admin.pages.settings.sample', ['label' => 'Composer label', 'usage' => 'components.composer-label', 'properties' => 'font-size,gap'])
      <div data-ds-preview>
        @include('components.composer-label', ['composer' => new \App\Composer(['name' => 'Sample composer'])])
      </div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Table actions', 'usage' => 'admin.components.table-action', 'properties' => 'font-size,padding,color,background-color,border,border-radius'])
      <div class="d-flex flex-wrap align-items-center gap-2" data-ds-preview>
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit']])
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete']])
        @include('admin.components.table-action', ['tableAction' => ['label' => 'Other action', 'icon' => 'hat-glasses', 'iconOnly' => true]])
      </div>
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Admin filter', 'usage' => 'admin.components.filters.form · fields · button', 'properties' => 'font-size,padding,border,border-radius,background-color'])
      @include('admin.components.filters.form', [
        'id' => 'ds-table-filters', 'label' => 'Sample table filters', 'preview' => true, 'fields' => [['type' => 'search', 'name' => 'ds_table_search', 'label' => 'Search accounts', 'placeholder' => 'Name or email', 'designSystemPreview' => true]],
        'buttons' => [['label' => 'Apply filters'], ['label' => 'Reverse order', 'secondary' => true, 'icon' => 'arrow-down']],
      ])
    @endcomponent
    @component('admin.pages.settings.sample', ['label' => 'Pagination', 'usage' => '.pagination + .page-link', 'properties' => 'font-size,padding,border,border-radius,background-color'])
      <div class="admin-table p-0 border-0"><nav aria-label="Sample pagination"><ul class="pagination"><li class="page-item disabled"><span class="page-link" aria-label="Previous page">@icon('chevron-left', ['mr' => 0])</span></li><li class="page-item active" aria-current="page"><span class="page-link" data-ds-preview>1</span></li><li class="page-item"><button class="page-link" type="button">2</button></li><li class="page-item"><button class="page-link" type="button" aria-label="Next page">@icon('chevron-right', ['mr' => 0])</button></li></ul></nav></div>
    @endcomponent
  </div>
</section>

<section class="design-system__section" id="ds-icons" aria-labelledby="ds-icons-title">
  <header class="design-system__section-heading"><span class="design-system__eyebrow">10 / ICONOGRAPHY</span><h2 id="ds-icons-title">Icons</h2><p>Lucide SVG icons inherit text color and size. The shared stroke width is <span data-ds-token="--icon-stroke-width"></span>.</p></header>
  <div class="design-system__icon-grid">
    @foreach(['music', 'search', 'heart', 'bookmark', 'play', 'pause', 'download', 'upload', 'pencil', 'trash', 'plus', 'x', 'check', 'chevron-right', 'users', 'settings', 'bell', 'circle-help'] as $icon)
      <div>@icon($icon, ['mr' => 0, 'classes' => 'icon-size-xl'])<code>{{ $icon }}</code></div>
    @endforeach
  </div>
  <div class="design-system__grid mt-4">
    @foreach(['thin' => 1, 'light' => 1.5, 'regular' => 2, 'bold' => 2.5] as $weight => $stroke)
      @component('admin.pages.settings.sample', ['label' => ucfirst($weight).' stroke', 'usage' => '.icon-weight-'.$weight, 'properties' => 'width,height,stroke-width'])
        <span data-ds-preview data-ds-icon>@icon('heart', ['mr' => 0, 'classes' => 'icon-size-2x icon-weight-'.$weight])</span>
      @endcomponent
    @endforeach
  </div>
</section>

@component('components.modal', ['id' => 'ds-example-modal', 'header' => 'Sample dialog', 'centered' => true, 'options' => ['body' => ['padding' => 3]]])
    @slot('body')
        This is a preview of PianoLIT’s shared modal. Try closing it with the button, the backdrop or Escape.
    @endslot
    @slot('footer')
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close preview</button>
    @endslot
@endcomponent

@component('components.modal', ['id' => 'ds-card-editor-example', 'header' => 'Add a new card', 'centered' => true, 'options' => ['body' => ['padding' => 3]]])
    @slot('body')
        <p class="text-muted mb-0">This tile opens the shared modal. In Billing, the same dialog loads the secure card fields and a live card preview.</p>
    @endslot
    @slot('footer')
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close preview</button>
    @endslot
@endcomponent

@foreach(['signup', 'premium'] as $kind)
    @include('components.access-modal', app(\App\Services\WebApp\AccessPrompts::class)->prompt('favorites', $kind) + ['id' => 'ds-'.$kind.'-prompt', 'preview' => true, 'actionUrl' => '#'])
@endforeach
