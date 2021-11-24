@php($impostor = session()->has('impersonator'))
<div class="position-relative"
@if($impostor)
 title="You are impersonating {{possessive(auth()->user()->first_name)}} account"
@endif
 >
@if($impostor)
<div class="absolute-center">
	@fa(['icon' => 'user-secret', 'mr' => 0, 'size' => '2x'])
</div>
@endif
<img src="{{asset(isset($admin) && $admin ? 'images/brand/admin-icon.svg' : 'images/brand/app-icon.svg')}}" alt="PianoLIT icon" class="mb-{{$mb ?? null}} {{$classes ?? null}} {{$impostor ? 'opacity-4' : null}}" style="border-radius: 20%; width: {{$size ?? '60px'}}">
</div>