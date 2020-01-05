<div class="input-group mb-2">
	<div class="input-group-prepend">
		<span class="input-group-text bg-transparent border-0" id="addon-wrapping"><i class="fas fa-dollar-sign"></i></span>
	</div>
	<input class="form-control text-right border-bottom {{validate($errors->default, 'per_'.$duration.'_fees[]')}}"
	step="5" type="number" name="per_{{$period}}_fees[]" max="500" min="0"
	value="{{$fields['per_'.$period.'_fees'][$duration] ?? null}}"style="max-width: 72px; border: none">
	<div class="input-group-append">
		<input type="checkbox" value="per {{$period}}" class="position-absolute invisible" name="lesson_duration[]">
		<span class="input-group-text bg-transparent border-0">per {{$period}}</span>
	</div>
</div>