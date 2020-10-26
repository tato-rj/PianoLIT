<div class="row">
		<div class="col-lg-9 col-md-9 col-12 mb-2">
			<div class="grid row">
				{{$items}}
			</div>
          <div class="d-flex justify-content-center">
            {{ $links }}
          </div>
		</div>
		<div class="col-lg-3 col-md-3 col-12 mb-2">
			<div class="mb-4">
				<div class="d-flex d-apart mb-1 pb-1 border-bottom">
					<p class="text-muted mb-0"><strong>TOPICS</strong></p>
					<a href="{{route(request()->route()->getName())}}" class="text-muted"><small>reset</small></a>
				</div>
		        <div>
		          @foreach($topics as $topic)
		          <a href="{{route(request()->route()->getName(), request('topics') == $topic->slug ? null : ['topics' => $topic->slug])}}" class="btn btn-{{request('topics') == $topic->slug ? 'primary' : 'light'}} m-1 btn-sm text-muted">{{ucfirst($topic->name)}}</a>
		          @endforeach
		        </div>
		    </div>

			{{$extra ?? null}}

			@include('components.display.ads')
		</div>
</div>
