<div class="m-2">
	<div class="d-flex align-items-center">
		<audio controls class="mr-2">
			<source src="{{storage($file)}}" type="audio/mp3">
		</audio>
		<div class="cursor-pointer remove-file" 
			data-path="{{route('admin.posts.audio.destroy', ['path' => $file])}}" 
			title="Remover o arquivo" style="top: 0; right: 0">
			<i class="fas fa-lg fa-times-circle text-danger"></i>
		</div>
	</div>

	<div>{{storage($file)}}</div>
</div>
