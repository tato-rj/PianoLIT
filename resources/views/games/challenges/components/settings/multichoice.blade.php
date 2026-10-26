  <div class="mb-2">
    <label class="nowrap">{{$label}}</label>
    <div class="d-flex flex-wrap">
      @foreach($options as $choice)
      <div class="m-1 position-relative">
        <input {{isset($singlechoice) ? 'singlechoice' : 'multichoice'}} name="{{$name}}[]" type="checkbox" value="{{$choice}}" class="invisible position-absolute top-0 left-0" id="{{str_slug($game)}}-{{$choice}}" {{in_array($choice, $settings->options($name)) ? 'checked' : null}} autocomplete="off">
        <label class="btn btn-{{in_array($choice, $settings->options($name)) ? 'secondary' : 'white'}}" for="{{str_slug($game)}}-{{$choice}}">
        {{isset($ucfirst) ? ucfirst($choice) : $choice}}
        </label>
      </div>
      @endforeach
    </div>
  </div>
