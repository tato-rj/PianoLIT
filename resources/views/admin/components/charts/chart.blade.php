<div id="stats-{{$type}}" data-url="{{$url}}" data-chart="{{$chart}}" class="mb-4">
  <div class="mx-2 mb-4 d-flex justify-content-between">
    <div>
      @isset($title)
      <h4 class="mb-1"><strong>{{$title}}</strong></h4>
      @endisset
      @isset($subtitle)
      <p class="m-0 text-grey">{{$subtitle ?? null}}</p>
      @endisset
    </div>
    <div class="d-flex flex-wrap">
      @if(! empty($buttons))
      <div class="btn-group btn-group-sm mx-1">
        @foreach($buttons as $name => $values)
          @foreach($values as $value)
          <button data-parent="#stats-{{$type}}" name="{{$name}}" value="{{$value}}" 
            class="form-control-sm chart-btn btn btn-{{$loop->first ? null : 'outline-'}}secondary" {{$loop->first ? 'selected' : null}}>{{ucfirst($value)}}</button>
          @endforeach
        @endforeach
      </div>
      @else
      <button class="chart-btn d-none" data-parent="#stats-{{$type}}" name="type" value="{{$type}}" selected></button>
      @endif
      @if(! empty($select))
      @foreach($select as $name => $inputs)
      <div class="mx-1 form-group-sm">
        <select class="chart-select form-control form-control-sm" data-xaxis="{{$xAxis ?? null}}" data-parent="#stats-{{$type}}">
          @foreach($inputs as $input)
          <option name="{{$name}}" value="{{$input['value']}}">{{$input['label']}}</option>
          @endforeach
        </select>
      </div>
      @endforeach
      @endif
    </div>
  </div>
  <div class="position-relative" style="height: {{$height ?? '40vh'}}">
    <canvas id="chart-{{$type}}" class="w-100" style="height: 100%"></canvas>
  </div>
</div>