<div></div>
<div id="app-search">
	<div class="container py-4">
		<div class="row">
			<div class="col-lg-8 col-sm-10 col-11 mx-auto rounded p-4 shadow bg-light">
				<div class="d-flex d-apart mb-3">
					<div>
						<div>
							<img class="animated fadeInLeft" src="{{asset('images/brand/app-icon.svg')}}" alt="PianoLIT icon" style="border-radius: 20%; width: 26px; display: none;">
							<span class="ml-1 text-muted">
								@if(request('search'))
									<strong>{{$results->count()}}</strong> {{str_plural('result', $results->count())}} for
								@else
									@auth
									Hi <strong>{{auth()->user()->first_name}}</strong>!
									@else
									Welcome<span class="hide-on-sm"> to <strong>PianoLIT</strong>!</span>
									@endauth
								@endif
							</span>
						</div>
					</div>
					<div class="text-nowrap">
						@cta(['type' => 'ios'])
						@cta(['type' => 'webapp'])
					
						@button([
							'href' => route('webapp.discover'),
							'nofollow' => true,
							'label' => auth()->check() ? 'MY WEBAPP' : 'FREE SIGNUP',
							'styles' => [
								'size' => 'sm',
								'theme' => 'primary',
								'pill' => true
							],
							'data' => ['ios' => config('app.stores.ios')],
							'classes' => 'free-trial-launch'])
					</div>
				</div>
				<div class="bg-white shadow-light rounded">
					<form method="GET" action="{{route('explore.search')}}" disable-on-submit submit-on-enter>
						<div class="input-group input-group-lg">
							<div class="input-group-prepend">
								<button disabled class="btn-raw pl-3">@fa(['icon' => 'music', 'mr' => 0, 'size' => 'lg', 'color' => 'grey'])</button>
							</div>
							<input type="text" name="search" value="{{request('search')}}" class="form-control border-0 form-transparent" placeholder="Search here...">
							<div class="input-group-append">
								<button class="btn-raw px-3" type="submit">@fa(['icon' => 'arrow-right', 'mr' => 0, 'size' => 'lg', 'color' => 'primary'])</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>