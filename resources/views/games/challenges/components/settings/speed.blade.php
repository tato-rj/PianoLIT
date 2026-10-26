  <div class="mb-2">
    <label for="{{$modalID ?? null}}-speed" class="nowrap">Speed</label>
    <input type="range" name="speedIndex" class="form-range mt-2 big-thumb" data-range-labels="#speedLabels" min="0" max="{{count($speeds) - 1}}" step="1" id="{{$modalID ?? null}}-range" value="{{$settings->options('speedIndex')}}">
    <div class="d-flex justify-content-between" id="speedLabels">
      @foreach($speeds as $speed)
      <label style="font-size: 70%" class="text-light lh-1 {{$loop->first || $loop->last ? 'mx-1' : null}}">{{$speed}}</label>
      @endforeach
    </div>
  </div>
