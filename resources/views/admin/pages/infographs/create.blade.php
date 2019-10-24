<form method="POST" action="{{route('admin.infographs.store')}}" class="mb-4" enctype="multipart/form-data">
	@csrf
	<div class="form-row form-group">
		<div class="col">
			<div class="form-group">
				<input type="text" name="name" value="{{old('name')}}" placeholder="Infograph name" class="form-control" required>
			</div>
			<div class="form-row">
				<div class="col">
					<select name="type" class="form-control">
						<option selected disabled>Type</option>
						@foreach(array_keys($types) as $type)
						<option value="{{$type}}" {{ old('type') == $type ? 'selected' : ''}}>{{ucfirst($type)}}</option>
						@endforeach
					</select>
				</div>
				<div class="col">
					<div class="custom-file">
						<input type="file" class="custom-file-input" name="cover_image" id="customFile" required>
						<label class="custom-file-label truncate" for="customFile">Image</label>
					</div>
				</div>
			</div>
		</div>
		<div class="col">
			<div class="h-100">
				<textarea name="description" class="form-control h-100" required placeholder="Description (max. 238 characters)">{{old('description')}}</textarea>
			</div>
		</div>
	</div>
	<div class="form-group text-right">
		<button type="submit" class="btn btn-sm btn-default">Create infograph</button>
	</div>
</form>