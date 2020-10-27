@if($ad['escore'])
<div class="position-relative mb-4 border border-3x border-light">
	<div class="text-center px-3 pt-5 pb-6">
		<div class="px-4 mb-3">
			<a href="{{$ad['escore']->showRoute()}}" class="link-none">
				@include('shop.components.cover', ['product' => $ad['escore'], 'maxWidth' => '300px'])
			</a>
		</div>
		<div class="mb-4">
			@if($mobile)
			<h5 class="mb-3"><strong>{{$ad['escore']->title}}</strong></h5>
			@else
			<h6 class="mb-3"><strong>{{$ad['escore']->title}}</strong></h6>
			@endif
			<div class="text-left">
				<ul class="list-unstyled">
					<li class="{{$mobile ? 'lead px-4' : null}}">@fa(['icon' => 'check', 'color' => 'green'])Instant Download</li>
					<li class="{{$mobile ? 'lead px-4' : null}}">@fa(['icon' => 'check', 'color' => 'green'])Lifetime Access</li>
					<li class="{{$mobile ? 'lead px-4' : null}}">@fa(['icon' => 'check', 'color' => 'green'])One click payment</li>
				</ul>
			</div>
		</div>
		<div>
			<a href="{{$ad['escore']->showRoute()}}" class="link-none border-bottom py-3 py-2 border-1x border-dark text-nowrap" 
				style="letter-spacing: 3px; {{$mobile ? 'font-size: 1.4rem' : null}}">
				MORE DETAILS @fa(['icon' => 'chevron-right', 'mr' => 0])
			</a>
		</div>
	</div>
</div>
@endif