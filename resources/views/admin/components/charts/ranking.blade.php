<div id="ranking-{{$type}}" data-url="{{$url}}" class="border py-4 px-3">
  <div class="mx-2 mb-4 d-flex justify-content-between">
    <div>
      <h4 class="mb-1"><strong>{{$title}}</strong></h4>
      <p class="m-0 text-grey">{{$subtitle ?? null}}</p>
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
        <select class="chart-select form-control form-control-sm" data-parent="#stats-{{$type}}">
          @foreach($inputs as $input)
          <option name="{{$name}}" value="{{$input['value']}}">{{$input['label']}}</option>
          @endforeach
        </select>
      </div>
      @endforeach
      @endif
    </div>
  </div>

  <div>
    <div class="table-responsive">
      <table class="table m-0 w-100">
        <thead>
          <tr>
            @foreach($headers as $label)
            <th class="border-0" scope="col">{!! $label !!}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($collection as $item)
            @include($row)
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>