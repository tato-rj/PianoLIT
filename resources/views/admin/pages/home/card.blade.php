<div class="col-lg-3 col-md-4 col-12 p-2">
  <div class="rounded shadow-light p-3">
    <div class="pb-2 border-bottom">
      <strong>{{number_format($current)}}</strong> {{$label}}
    </div>
    <div class="pt-2">
      @if($past == $current)
      <small class="text-warning">@fa(['icon' => 'exclamation-circle'])Same as last week</small>
      @elseif($past > $current)
      <small class="text-red">@fa(['icon' => 'arrow-down'])Down {{$past - $current}} from last week</small>
      @else
      <small class="text-green">@fa(['icon' => 'arrow-up'])Up {{$current - $past}} from last week</small>
      @endif
    </div>
  </div>
</div>