<div class="m-3" style="max-width: 260px">
	<div class="position-relative w-100 shadow-sm">
		<img src="{{storage($file['file'])}}" class="w-100">
		<div class="show-on-hover">
		    <div class="m-0 absolute-center z-10 text-center">
		        <a class="link-none" href="">
		        	<div class="cursor-pointer text-warning clip mb-4" data-clipboard-text="/storage/{{$file['file']}}" data-toggle="tooltip" title="Copied!" data-trigger="manual"><strong>copy</strong></div>
		        </a>
		        <button class="border-0 bg-transparent p-0 text-danger cursor-pointer remove-file font-weight-bold" data-path="{{route('admin.quizzes.media.destroy', ['path' => $file['file']])}}">delete</button>
		    </div>
		    <div class="overlay w-100 h-100 bg-white z-0" style="opacity: 0.95"></div>                
		</div>
	</div>
</div>