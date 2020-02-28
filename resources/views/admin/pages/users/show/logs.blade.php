@include('admin.pages.users.show.title', ['title' => 'Activity Logs', 'icon' => 'clipboard-check'])

<div class="row">
  <div class="col-12 mb-4">
    @include('admin.pages.users.show.logs.app.table')
    @include('admin.pages.users.show.logs.web.table')
  </div>
</div>

@modal(['title' => 'Log data', 'size' => 'lg'])
<div id="data-container"></div>
@endmodal