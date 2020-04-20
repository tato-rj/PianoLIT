@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        @table([
          'id' => 'trials-table',
          'title' => '<i class="fas fa-hourglass-half mr-2 text-warning"></i>Trials ('.$trials->count().')',
          'sortable' => true,
          'more' => route('admin.memberships.load-trials'),
          'headers' => ['ID <i class="fas fa-sort"></i>', 'User <i class="fas fa-sort"></i>', 'Plan <i class="fas fa-sort"></i>', 'Progress <i class="fas fa-sort"></i>', 'Ends at <i class="fas fa-sort"></i>'],
          'rows' => view('admin.pages.stats.memberships.trials', ['memberships' => $trials->take(10)])
        ])
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        @table([
          'id' => 'members-table',
          'title' => '<i class="fas fa-credit-card mr-2 text-green"></i>Active memberships ('.$members->count().')',
          'sortable' => true,
          'more' => route('admin.memberships.load-members'),
          'headers' => ['ID <i class="fas fa-sort"></i>', 'User <i class="fas fa-sort"></i>', 'Plan <i class="fas fa-sort"></i>', 'Time until next renewal <i class="fas fa-sort"></i>', 'Renews at <i class="fas fa-sort"></i>'],
          'rows' => view('admin.pages.stats.memberships.members', ['memberships' => $members->take(10)])
        ])
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        @table([
          'id' => 'expired-table',
          'title' => '<i class="fas fa-credit-card mr-2 text-muted"></i>Expired memberships ('.$expired->count().')',
          'sortable' => true,
          'more' => route('admin.memberships.load-expired'),
          'headers' => ['ID <i class="fas fa-sort"></i>', 'User <i class="fas fa-sort"></i>', 'Plan <i class="fas fa-sort"></i>', 'Time since last renewal <i class="fas fa-sort"></i>', 'Last renew at <i class="fas fa-sort"></i>'],
          'rows' => view('admin.pages.stats.memberships.expired', ['memberships' => $expired->take(10)])
        ])
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">

</script>

@endsection
