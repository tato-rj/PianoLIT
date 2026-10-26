<div class="bg-light mb-3" style="border-radius: 26px;">
	<label class="fw-bold text-muted mb-2 w-100 rounded-top px-3 py-1" style="font-size: 70%; background: rgba(232,233,234)">@include('games.challenges.components.icon', ['icon' => 'gear', 'mr' => 1]){{$title}}</label>
	<div class="py-2 px-3">
		{{$slot}}
	</div>
</div>
