  <div class="mb-2">
    <label for="{{$modalID ?? null}}-range" class="nowrap">Number of accidentals</label>
    <input type="range" name="numberOfAccidentals" class="form-range mt-2 big-thumb" data-range-labels="#numberOfAccidentalsLabels" min="0" max="2" step="1" id="{{$modalID ?? null}}-range" value="{{$settings->options('numberOfAccidentals')}}">
    <div class="d-flex justify-content-between" id="numberOfAccidentalsLabels">
      @foreach($settings->getNumberOfAccidentals() as $index => $label)
      <label style="font-size: 70%" class="text-light lh-1 {{$loop->iteration == 2 ? 'mr-2' : null}}">{{$label}}</label>
      @endforeach
    </div>
  </div>
