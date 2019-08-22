<div class="m-3">
	<div class="text-muted mb-2"><strong>{{basename($file['file'])}}</strong></div>
	<audio controls class="mb-2 d-block">
		<source src="{{storage($file['file'])}}" type="audio/mp3">
	</audio>
	<div class="d-flex justify-content-between w-100">
		<button class="btn btn-danger btn-sm remove-file px-2" data-path="{{route('admin.quizzes.audio.destroy', ['path' => $file['file']])}}" style="border-radius: 20px;">
			<i class="fas fa-trash-alt mr-2"></i><strong>Delete</strong>
		</button>

		<button class="btn btn-warning btn-sm px-2 clip" data-clipboard-text="/storage/{{$file['file']}}" data-toggle="tooltip" title="Copied!" data-trigger="manual" style="border-radius: 20px;">
			<i class="fas fa-copy mr-2"></i><strong>Copy</strong>
		</button>
	</div>
</div>