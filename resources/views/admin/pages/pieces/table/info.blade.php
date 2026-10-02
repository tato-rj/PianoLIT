<div class="d-flex hide-on-sm">
  @include('admin.components.play', ['audio' => storage($item->audio_path)])
  <span class="text-nowrap mx-1 {{$item->tutorials_count > 0 ? 'text-primary' : 'text-muted'}}">@icon('brand-youtube', ['mr' => 0])</span>
  <span class="{{$item->hasTutorials(['synthesia']) ? 'text-danger' : 'text-muted'}}">@icon('flame', ['mr' => 0])</span>
</div>

