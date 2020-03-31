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
          'title' => 'Trials',
          'hoverable' => 'no',
          'minWidth' => 820,
          'borderless' => true,
          'headers' => ['User <i class="fas fa-sort"></i>', 'Progress <i class="fas fa-sort"></i></th>', 'Ends at <i class="fas fa-sort"></i></th>'],
          'rows' => view('admin.pages.stats.memberships.trials', ['memberships' => $trials, 'limit' => 10, 'more' => true])
        ])
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        @table([
          'id' => 'members-table',
          'title' => 'Memberships',
          'hoverable' => 'no',
          'minWidth' => 820,
          'borderless' => true,
          'headers' => ['User <i class="fas fa-sort"></i>', 'Time until next renewal <i class="fas fa-sort"></i></th>', 'Renews at <i class="fas fa-sort"></i></th>'],
          'rows' => view('admin.pages.stats.memberships.members', ['memberships' => $members, 'limit' => 10, 'more' => true])
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
