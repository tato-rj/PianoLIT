<form method="POST" action="{{route('admin.infographs.store')}}" class="mb-4" enctype="multipart/form-data">
	@csrf
	<div class="form-row form-group">
		<div class="col">
			<div class="form-group">
				<input type="text" name="name" value="{{old('name')}}" placeholder="Infograph name" class="form-control" required>
			</div>
			<div class="">
				<div class="custom-file">
					<input type="file" class="custom-file-input" name="cover_image" id="customFile" required>
					<label class="custom-file-label truncate" for="customFile">Image</label>
				</div>
			</div>
		</div>
		<div class="col">
			<div class="h-100">
				<textarea name="description" class="form-control h-100" required placeholder="Description (max. 238 characters)">{{old('description')}}</textarea>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="rounded bg-light px-3 py-2 mb-3">
			<p class="text-brand border-bottom pb-1 mb-1"><strong>TOPICS</strong></p>
			<div class="d-flex flex-wrap">
				@foreach($topics as $topic)
				<div class="custom-control custom-checkbox mx-2 mb-2">
					<input type="checkbox" class="custom-control-input" name="topics[]" value="{{$topic->id}}" id="{{$topic->name}}">
					<label class="custom-control-label" for="{{$topic->name}}">{{$topic->name}}</label>
				</div>
				@endforeach
			</div>
		</div>
	</div>
	<div class="form-group text-right">
		<button type="submit" class="btn btn-sm btn-default">Create infograph</button>
	</div>
</form>