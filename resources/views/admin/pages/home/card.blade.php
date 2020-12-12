<div class="col-lg-3 col-md-4 col-12 mb-3">
  <div class="rounded border d-flex">
    <div class="flex-grow p-3">
      <div class="pb-2 border-bottom">
          <strong>{{number_format($stat['counts'][1])}}</strong> {{$stat['label']}}
      </div>
      <div class="pt-2">
        @if($stat['counts'][0] == $stat['counts'][1])
        <small class="text-warning">@fa(['icon' => 'exclamation-circle'])Same as last week</small>
        @elseif($stat['counts'][0] > $stat['counts'][1])
        <small class="text-red">@fa(['icon' => 'arrow-down'])Down {{$stat['counts'][0] - $stat['counts'][1]}} from last week</small>
        @else
        <small class="text-green">@fa(['icon' => 'arrow-up'])Up {{$stat['counts'][1] - $stat['counts'][0]}} from last week</small>
        @endif
      </div>
    </div>
    <a class="text-muted" href="{{$stat['url']}}">
      <div class="d-flex flex-center p-2 bg-light rounded-right h-100">
        @fa(['icon' => 'arrow-right', 'size' => 'g', 'mr' => 0])
      </div>
    </a>
  </div>
</div>