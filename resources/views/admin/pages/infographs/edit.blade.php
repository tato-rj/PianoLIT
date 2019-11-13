@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Infographs',
    'description' => 'Edit the infographs'])

	<div class="row mb-5">
		<div class="col-4">
			<img src="{{storage($infograph->cover_path)}}" class="w-100">
		</div>
		<div class="col-8">
			<div class="mb-4 bg-light rounded px-4 py-3">
				<h6 class="text-grey">This infrograph has been downloaded <strong class="text-muted">{{$infograph->downloads}}</strong> {{str_plural('time', $infograph->downloads)}}.</h6>
				<h6 class="text-grey m-0">The current score is <strong class="text-muted">{{$infograph->score}}</strong>.</h6>
			</div>
			<form method="POST" action="{{route('admin.infographs.update', $infograph->slug)}}" enctype="multipart/form-data">
				@method('PATCH')
				@csrf
				<div class="form-row form-group">
					<div class="col">
						<div class="form-group">
							<input type="text" name="name" value="{{$infograph->name}}" placeholder="Infograph name" class="form-control" required>
						</div>
						<div class="form-row">
							<div class="col">
								<div class="custom-file">
									<input type="file" class="custom-file-input" name="cover_image" id="customFile">
									<label class="custom-file-label truncate" for="customFile">Image</label>
								</div>
							</div>
						</div>
					</div>
					<div class="col">
						<div class="h-100">
							<textarea name="description" class="form-control h-100" placeholder="Description (max. 238 characters)" required>{{$infograph->description}}</textarea>
						</div>
					</div>
				</div>
				<div class="form-group">
				  <div class="rounded bg-light px-3 py-2 mb-3">
				    <p class="text-brand border-bottom pb-1 mb-1"><strong>TOPICS</strong></p>
				    <div class="d-flex flex-wrap">
				        @foreach($topics as $topic)
				        <div class="custom-control custom-checkbox mx-2 mb-2">
				          <input type="checkbox" class="custom-control-input" name="topics[]" value="{{$topic->id}}" id="{{$topic->name}}" {{($infograph->topics->contains($topic->id)) ? 'checked' : ''}}>
				          <label class="custom-control-label" for="{{$topic->name}}">{{$topic->name}}</label>
				        </div>
				        @endforeach
				    </div>
				  </div>
				</div>
				<div class="form-group text-right">
					<button type="submit" class="btn btn-sm btn-default">Update infograph</button>
				</div>
			</form>
		</div>
	</div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">

</script>
@endsection