@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['icon' => 'code', 'title' => 'Discover', 'subtitle' => 'Preview and refresh the web app’s free pick and featured composers.'])

    <div class="admin-discover">
      <div class="admin-discover-controls">
        <div>
          <h2>Web app preview</h2>
          @php
            $refreshAt = preg_match('/^app\.discover-(\d+)$/', $key ?? '', $matches)
                ? carbon((int) $matches[1])->addDay() : null;
          @endphp
          <p class="admin-discover-refresh-time">{{ $refreshAt && $refreshAt->isFuture() ? 'Auto refresh ' . $refreshAt->diffForHumans() : 'Refreshes automatically each day' }}</p>
        </div>
        <form method="POST" action="{{route('redis.update')}}">
          @csrf
          <button type="submit" class="btn btn-outline-success btn-sm">@icon('refresh-cw', ['mr' => 1])Refresh discover page</button>
        </form>
      </div>

      <section class="admin-discover-preview" aria-label="Discover preview">
        @foreach(['freepick', 'composers'] as $rowName)
          @php($row = $collection->firstWhere('row', $rowName))
          @if($row && count($row['content']))
            @include('webapp.discover.rows.' . $rowName, ['newTab' => true])
          @else
            <div class="mb-4">
              <h5 class="mb-3">{{ $rowName === 'freepick' ? 'Free weekly pick' : 'Composers' }}</h5>
              <p class="text-muted">{{ $rowName === 'freepick' ? 'No free pick is selected.' : 'No featured composers are available.' }}</p>
            </div>
          @endif
        @endforeach
      </section>

      <a href="{{route('webapp.discover')}}" target="_blank" rel="noopener noreferrer" class="admin-discover-open">Open Discover in the web app @icon('arrow-up-right', ['mr' => 0])</a>
    </div>
  </div>
</div>
@endsection
