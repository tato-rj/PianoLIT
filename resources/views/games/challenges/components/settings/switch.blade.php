<div class="d-apart mb-2">
  <label>{{$label}}</label>
  <div class="btn-group">
    @foreach($options as $option)
    <div>
      <input multichoice name="{{$name}}[]" type="checkbox" value="{{$option}}" class="invisible position-absolute top-0 left-0" id="{{str_slug($game)}}-{{$option}}" {{in_array($option, $settings->options($name)) ? 'checked' : null}} autocomplete="off">
      <label class="btn btn-sm px-3 btn-{{in_array($option, $settings->options($name)) ? 'secondary' : 'white'}}" for="{{str_slug($game)}}-{{$option}}">
        {{ucfirst($option)}}
        </label>
      </div>
    @endforeach
  </div>
</div>
