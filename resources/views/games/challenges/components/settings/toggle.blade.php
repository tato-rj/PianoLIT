<div class="d-apart mb-2">
  <label>
    @isset($icon)
    @include('games.challenges.components.icon', ['icon' => $icon, 'mr' => 1, 'fa_color' => 'blue opacity-6'])
    @endisset
    {{$label}}
  </label>
  @include('games.challenges.components.toggle', ['name' => $name, 'on' => $settings->options($name), 'label' => null, 'ariaLabel' => $label])
</div>
