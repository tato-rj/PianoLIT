<div data-name="{{$names[0]}}" data-octave="{{$octave}}" data-names="{{json_encode(enharmonic()->find($names[0]))}}" class="keyboard-white-key keyboard-key {{ ($responsive ?? false) ? 'bg-white' : '' }} h-100 border rounded-bottom position-relative d-flex align-items-end justify-content-center pb-2 shadow-sm {{$styles ?? null}}" style="width: {{ ($responsive ?? false) ? 'var(--piano-white-key-width)' : '40px' }}; cursor: pointer;">
	@if($names[1])
	<div data-name="{{$names[1]}}" data-octave="{{$octave}}" data-names="{{json_encode(enharmonic()->find($names[1]))}}" style="height: 60%; width: {{ ($responsive ?? false) ? 'var(--piano-black-key-width)' : '36px' }}; top: -1.4px; right: {{ ($responsive ?? false) ? 'var(--piano-black-key-offset)' : '-21.3px' }}; z-index: 1; background-color: #171a1c" class="keyboard-black-key keyboard-key bg-dark rounded-bottom border position-absolute d-flex align-items-end justify-content-center pb-2">
		<div class="dot rounded-circle bg-warning" style="width: {{ ($responsive ?? false) ? 'var(--piano-dot-size)' : '22px' }}; height: {{ ($responsive ?? false) ? 'var(--piano-dot-size)' : '22px' }}; display: {{$highlight[1] ? 'block' : 'none'}}"></div>
	</div>
	@endif

	<div class="dot rounded-circle bg-warning" style="width: {{ ($responsive ?? false) ? 'var(--piano-dot-size)' : '23px' }}; height: {{ ($responsive ?? false) ? 'var(--piano-dot-size)' : '23px' }}; display: {{$highlight[0] ? 'block' : 'none'}}"></div>
</div>
