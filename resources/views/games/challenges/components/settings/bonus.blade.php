<div class="bg-blue-lighter mb-3" style="border-radius: 26px;">
	<label class="fw-bold text-blue mb-2 w-100 rounded-top px-3 py-1 bg-blue-light d-apart" style="font-size: 70%;">
		<div>@include('games.challenges.components.icon', ['icon' => 'gem', 'mr' => 1])BONUS - these will give you higher points!</div>
	</label>
	<div class="py-2 px-3">
		{{$slot}}
	</div>
</div>
