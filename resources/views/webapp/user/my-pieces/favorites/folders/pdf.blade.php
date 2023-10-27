<div class="text-center w-100">
	<a class="btn btn-primary-outline btn-wide {{$classes ?? null}}" data-toggle="modal" data-target="#generate-pdf-folder-{{$folder->id}}">@fa(['icon' => 'book', 'classes' => ''])eScore</a>
</div>

@component('components.modal', ['id' => 'generate-pdf-folder-'.$folder->id, 'header' => 'Create eScore'])
@slot('body')
<p class="text-center mb-3">Customize the cover page below👇</p>

<form target="_blank" method="GET" action="{{route('webapp.users.favorites.folders.pdf', $folder)}}">
	<div class="mb-3 bg-white border mx-auto p-4 d-apart flex-column shadow-lg" style="height: 466px; max-width: 375px; font-family: serif;">
		<div>
			<div class="text-left w-100 mb-4">
				<input class="form-control border-0 bg-light" type="text" name="title" value="{{$folder->name}}" style="font-size: 2rem; font-weight: bold;" >
			</div>

			<div class="text-left w-100 border-bottom mb-1 pb-1">
				<input class="form-control border-0 bg-light" type="text" name="subtitle" value="A collection of pieces" style="font-size: 1.1rem;">
			</div>

			<div class="text-left w-100">
				<input class="form-control border-0 bg-light" type="text" name="comment" value="for piano" style="font-size: 1rem;">
			</div>
		</div>

		<div class="text-center">
			<div class="d-block m-0" style="font-size: 75%;">PianoLIT eScore</div>
			<div style="font-size: 75%;">created by {{auth()->user()->full_name}}</div>
		</div>
	</div>

	<div class="text-center">
		<button type="submit" class="btn btn-primary mx-auto">Create my eScore</button>
	</div>
</form>
@endslot
@endcomponent