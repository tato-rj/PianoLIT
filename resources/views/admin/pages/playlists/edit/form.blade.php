<div class="bg-light rounded px-4 py-3">
	<form method="POST" action="{{route('admin.playlists.update', $playlist->id)}}">
		@csrf
		@method('PATCH')
		<div class="row mb-2">
			<div class="col-5">
				<label class="text-muted">Basic information</label>
				<input type="text" name="name" placeholder="Name" class="form-control mb-2" value="{{$playlist->name}}" required>
				<input type="text" name="subtitle" placeholder="Subtitle" class="form-control mb-2" value="{{$playlist->subtitle}}" required>
				<select name="group" class="form-control mb-2" required>
					<option selected disabled>Select the group</option>
					<option value="journey" {{ $playlist->group == 'journey' ? 'selected' : ''}}>Journey</option>
				</select>
				<textarea name="description" placeholder="Description" class="form-control" rows="6" maxlength="255" required>{{$playlist->description}}</textarea>
			</div>
			<div class="col-7">
				<label class="text-muted">Pieces</label>
				<div id="playlist-pieces" class=""> 
					@foreach($playlist->pieces as $piece)
					@include('admin.pages.playlists.edit.piece', ['is_model' => false])
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