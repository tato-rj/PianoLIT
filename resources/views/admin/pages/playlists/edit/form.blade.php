<div class="bg-light rounded px-4 py-3">
	<form method="POST" action="{{route('admin.playlists.update', $playlist->id)}}" enctype="multipart/form-data">
		@csrf
		@method('PATCH')
		<div class="row mb-2">
			<div class="col-5">
				<label class="text-muted">Basic information</label>
				<div class="d-flex">
					<div>
						@if($playlist->cover_image)
						<div style="width: 138px" class="pr-2">
							<img src="{{$playlist->cover_image}}" class="w-100 rounded">
						</div>
						@endif
					</div>
					<div class="flex-grow">
						<input type="text" name="name" placeholder="Name" class="form-control mb-2" value="{{$playlist->name}}" required>
						<input type="text" name="subtitle" maxlength="72" placeholder="Subtitle" class="form-control mb-2" value="{{$playlist->subtitle}}" required>
						<input type="text" name="featured" maxlength="16" placeholder="Featured tag (optional)" class="form-control mb-2" value="{{$playlist->featured}}">
					</div>
				</div>
				<div class="custom-file mb-2">
					<input type="file" class="custom-file-input" name="cover" id="customFile">
					<label class="custom-file-label truncate" for="customFile">Upload</label>
				</div>
				<textarea name="description" placeholder="Description" class="form-control mb-2" rows="5" maxlength="255" required>{{$playlist->description}}</textarea>
			</div>
			<div class="col-7">
				<label class="text-muted">Pieces</label>
				<div id="playlist-pieces" class=""> 
					@foreach($playlist->pieces as $piece)
					@include('admin.pages.playlists.edit.piece')
					@endforeach
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-12 text-right">
				<button type="submit" class="btn btn-sm btn-default">Update playlist</button>
			</div>
		</div>
	</form>
</div>