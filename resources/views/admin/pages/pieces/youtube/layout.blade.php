<div class="rounded bg-light px-3 py-2 mb-3">
	<div class="d-flex justify-content-between pb-1 mb-3 text-brand border-bottom">
	  <p class="m-0"><strong>YOUTUBE</strong></p>
	  <p class="m-0">@include('admin.components.link', ['link' => "https://www.youtube.com"])</p>
	</div>

  @include('admin.pages.pieces.youtube.input', [
    'display' => 'none',
    'type' => 'original-type'])

  {{$slot ?? null}}

  <a class="add-new-field text-warning cursor-pointer d-block text-center" data-type="youtube">
    <small><i class="fas fa-plus mr-2"></i>Add a new one</small>
  </a>
</div>