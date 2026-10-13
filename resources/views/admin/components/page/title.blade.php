<header class="admin-page-header" id="page-title">
  <div class="admin-page-header-row">
    <div class="admin-page-heading">
      <h1 class="admin-page-title">{{$title}}<span class="admin-heading-dot" aria-hidden="true">.</span></h1>
      @if(!empty($subtitle))<p class="admin-page-subtitle">{{$subtitle}}</p>@endif
    </div>
    <div class="admin-page-tools">
      <span class="admin-today">{{now()->format('l, M j')}}</span>
      @include('admin.layouts.header.notifications')
      @include('admin.layouts.header.account')
    </div>
  </div>

  @if((isset($slot) && trim((string) $slot) !== '') || isset($action['url']) || isset($action['external_link']) || isset($action['modal']) || !empty($back))
  <div class="admin-page-actions">
    @isset($slot){{$slot}}@endisset
    @isset($action['url'])
    <a href="{{$action['url']}}" class="btn btn-sm btn-primary">@icon('plus'){{$action['label']}}</a>
    @endisset
    @isset($action['external_link'])
    <a href="{{$action['external_link']}}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">@icon('external-link'){{$action['label']}}</a>
    @endisset
    @isset($action['modal'])
    <button type="button" data-bs-toggle="modal" data-bs-target="#{{$action['modal']}}" class="btn btn-sm btn-primary">@icon('plus'){{$action['label']}}</button>
    @endisset
    @isset($back)
      @foreach($back as $label => $url)
      <a href="{{$url}}" class="btn btn-sm btn-secondary">@icon('circle-arrow-left'){{$label}}</a>
      @endforeach
    @endisset
  </div>
  @endif
</header>
