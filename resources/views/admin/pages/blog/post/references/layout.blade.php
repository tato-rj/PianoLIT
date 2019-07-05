<div class="rounded bg-light px-3 py-2 mb-3">
	<div class="d-flex justify-content-between pb-1 mb-3 text-brand border-bottom">
	  <p class="m-0"><strong>REFERENCES</strong></p>
	</div>

  @include('admin.pages.blog.post.references.input', [
    'display' => 'none',
    'type' => 'original-type'])

  {{$slot ?? null}}

  <a class="add-new-field text-warning cursor-pointer d-block text-center" data-type="reference">
    <small><i class="fas fa-plus mr-2"></i>Add a new one</small>
  </a>
</div>