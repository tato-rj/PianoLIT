<div class="d-apart mb-2">
  <label class="nowrap">{{$label}}</label>
  <div class="d-center form-number">
    <button style="touch-action: manipulation;" type="button" data-direction="down" class="btn-raw text-dark">@include('games.challenges.components.icon', ['icon' => 'minus', 'mr' => 0, 'fa_size' => 'xl'])</button>
    <input type="text" readonly name="{{$name}}" max="{{$max}}" min="{{$min}}" value="{{$settings->options($name)}}" class="form-control form-control-sm py-1 mx-2 text-center border-light" style="width: 52px;">
    <button style="touch-action: manipulation;" type="button" data-direction="up" class="btn-raw text-dark">@include('games.challenges.components.icon', ['icon' => 'plus', 'mr' => 0, 'fa_size' => 'xl'])</button>
  </div>
</div>
