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
          'borderless' => true,
          'headers' => ['User <i class="fas fa-sort"></i>', 'Progress <i class="fas fa-sort"></i></th>'],
          'rows' => view('admin.pages.stats.memberships.trials', ['memberships' => $trials, 'limit' => 10, 'more' => true])
        ])
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        @table([
          'id' => 'members-table',
          'title' => 'Members',
          'hoverable' => 'no',
          'borderless' => true,
          'headers' => ['User <i class="fas fa-sort"></i>', 'Progress <i class="fas fa-sort"></i></th>'],
          'rows' => view('admin.pages.stats.memberships.trials', ['memberships' => $members, 'limit' => 10, 'more' => true])
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
