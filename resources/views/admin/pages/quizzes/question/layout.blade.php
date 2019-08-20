<div class="rounded bg-light px-3 py-2 mb-3">
	<div class="pb-1 mb-1 text-brand border-bottom">
	  <p class="m-0"><strong>QUESTIONS</strong></p>
	</div>
  <p class="text-muted"><small>Insert <strong>[x]</strong> for a correct answer and <strong>[audio/url]</strong> to add an audio to a question</small></p>

  @include('admin.pages.quizzes.question.input', [
    'display' => 'none',
    'type' => 'original-type'])

  {{$slot ?? null}}

  <a class="add-new-field text-warning cursor-pointer d-block text-center" data-type="itunes">
    <small><i class="fas fa-plus mr-2"></i>Add a new one</small>
  </a>
</div>