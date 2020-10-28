<div class="position-relative mb-4 border border-light border-3x ad-banner
	@isset($mobile)
		{{$mobile ? 'show-on-sm' : 'hide-on-sm'}}
	@endisset
 ">
	<div class="text-center px-2 d-flex {{$vertical ? 'flex-wrap py-5' : 'py-2'}} justify-content-center align-items-center">
		<div class="px-3 my-2">
			{{$header ?? null}}

			<div>
				<a href="{{$ad->showRoute()}}" class="link-none">
					<div style="{{$vertical ? null : 'max-width: 220px; margin: 0 auto'}}">

					{{$image}}

					</div>
				</a>
			</div>
		</div>

		<div class="px-3 my-2" style="{{$vertical ? null : 'max-width: 420px'}}">
			<div class="mb-4">

				{{$beforeTitle ?? null}}

				@isset($mobile)
					@if($mobile)
					<h5 class="mb-3"><strong>{{$ad->title}}</strong></h5>
					@else
					<h6 class="mb-3"><strong>{{$ad->title}}</strong></h6>
					@endif
				@else
				<h5 class="mb-3"><strong>{{$ad->title}}</strong></h5>
				@endisset

				{{$afterTitle ?? null}}

			</div>
			<div>
				<a href="{{$ad->showRoute()}}" class="link-none border-bottom py-3 py-2 border-1x border-dark text-nowrap" 
					style="letter-spacing: 3px;
					@isset($mobile)
						{{$mobile ? 'font-size: 1.4rem' : null}}
					@endisset
					 ">

					{{$action}} 

					@fa(['icon' => 'chevron-right', 'mr' => 0])
				</a>
			</div>
		</div>

	</div>
</div>