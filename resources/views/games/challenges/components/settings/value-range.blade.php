@php
  $rangeId = $id ?? str_slug(($modalID ?? 'settings').'-'.$name);
  $rangeValue = $value ?? $settings->options($name);
  $thumbWidth = $thumbWidth ?? 52;
@endphp

<div class="mb-3 value-thumb-range-field">
  <label for="{{$rangeId}}">{{$label}}</label>

  <div class="value-thumb-range" style="--value-thumb-width: {{$thumbWidth}}px;">
    <input
      id="{{$rangeId}}"
      type="range"
      name="{{$name}}"
      class="form-range value-thumb-range__input"
      min="{{$min}}"
      max="{{$max}}"
      step="{{$step ?? 1}}"
      value="{{$rangeValue}}"
      data-value-thumb-range
      data-value-thumb-width="{{$thumbWidth}}">
    <output class="value-thumb-range__value" for="{{$rangeId}}">{{$rangeValue}}</output>
  </div>

{{--   <div class="value-thumb-range__limits">
    <span>{{$minLabel ?? $min}}</span>
    <span>{{$maxLabel ?? $max}}</span>
  </div> --}}
</div>
