<div class="d-apart">
  @if(isset($label))
  <h6 class="mr-2 mb-0">
    {{$label ?? null}}
  </h6>
  @endif
  <label class="switch">
    <input name="{{$name}}" data-url="{{$url ?? null}}" type="checkbox" role="switch" aria-label="{{ $ariaLabel ?? $label ?? $name }}" {{$on ? 'checked' : null}}>
    <span class="slider"></span>
  </label>
</div>
