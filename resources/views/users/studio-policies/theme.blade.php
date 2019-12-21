@php($themeSelected = empty($studioPolicy) ? old('theme') : $studioPolicy->get('theme'))

<div class="col-lg-4 col-6 mb-4">
	<div class="card w-100 cursor-pointer hover-shadow theme-option {{$themeSelected == $theme ? 'theme-selected' : null}}" style="opacity: .6">
		<div class="theme-check absolute-bottom-right" style="display: none;"><i class="fas fa-check-circle text-green"></i></div>
		<img src="{{asset('images/temp/themes/' . $theme . '.jpg')}}" class="card-img-top border-bottom" alt="...">
		<div class="card-body p-3">
			<div class="d-flex justify-content-between mb-2">
				<h6 class="card-title m-0">{{slug_str($theme)}}</h6>
				<div style="width: 40px" class="rounded d-flex">
					@foreach($info['colors'] as $color)
					<div style="background-color: {{$color}}; width: 33%; height: 90%"></div>
					@endforeach
				</div>
			</div>
			<p class="card-text" style="line-height: 1"><small>{{$info['description']}}</small></p>
		</div>
	</div>
	<input 
		required 
		type="radio" 
		name="theme" 
		value="{{$theme}}" 
		@if(!empty($themeSelected) || $themeSelected == 0) {{! is_null($themeSelected) && $themeSelected == $theme ? 'checked' : null}} @endif
		style="position: absolute; opacity: 0;">
</div>