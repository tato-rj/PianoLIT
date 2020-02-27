@include('admin.pages.users.show.title', ['title' => 'Activity Logs (' . $user->logs_count . ')'])

<div class="row">
  <div class="col-12">
    @include('admin.pages.users.show.logs.app.table')
    @include('admin.pages.users.show.logs.web.table')
  </div>
</div>

@modal(['title' => 'Log data', 'size' => 'lg'])
<div id="data-container"></div>
@endmodal