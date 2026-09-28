@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 mb-4">
        <div>Stripe memberships: {{\App\Billing\Sources\Stripe::where('status', 'active')->count()}}</div>
      </div>
      <div class="col-12">
        @table([
          'id' => 'trials-table',
          'title' => \App\Support\Icon::render('hourglass', ['mr' => 2, 'classes' => 'text-warning']) . 'Trials ('.$trials->count().')',
          'sortable' => true,
          'borderless' => true,
          'hoverable' => 'no',
          'more' => route('admin.memberships.load-trials'),
          'headers' => ['ID ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'User ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Plan ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Progress ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Ends at ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . ''],
          'rows' => view('admin.pages.stats.memberships.trials', ['memberships' => $trials->take(10)])
        ])
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        @table([
          'id' => 'members-table',
          'title' => \App\Support\Icon::render('credit-card', ['mr' => 2, 'classes' => 'text-green']) . 'Active memberships ('.$members->count().')',
          'sortable' => true,
          'borderless' => true,
          'hoverable' => 'no',
          'more' => route('admin.memberships.load-members'),
          'headers' => ['ID ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'User ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Plan ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Time until next renewal ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Renews at ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . ''],
          'rows' => view('admin.pages.stats.memberships.members', ['memberships' => $members->take(10)])
        ])
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        @table([
          'id' => 'expired-table',
          'title' => \App\Support\Icon::render('credit-card', ['mr' => 2, 'classes' => 'text-muted']) . 'Expired memberships ('.$expired->count().')',
          'sortable' => true,
          'borderless' => true,
          'hoverable' => 'no',
          'more' => route('admin.memberships.load-expired'),
          'headers' => ['ID ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'User ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Plan ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Time since last renewal ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Last renew at ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . ''],
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
