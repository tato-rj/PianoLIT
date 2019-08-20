<div class="rounded bg-light px-3 py-2 mb-3">
	<div class="pb-1 mb-1 text-brand border-bottom">
	  <p class="m-0"><strong>FEEDBACK</strong></p>
	</div>

  <input class="form-control-sm form-control mb-1" placeholder="0% to 24%" name="feedback[]" value="{{! empty($quiz) ? $quiz->feedback[0] : old('feedback.0')}}">
  <input class="form-control-sm form-control mb-1" placeholder="25% to 49%" name="feedback[]" value="{{! empty($quiz) ? $quiz->feedback[1] : old('feedback.1')}}">
  <input class="form-control-sm form-control mb-1" placeholder="50% to 74%" name="feedback[]" value="{{! empty($quiz) ? $quiz->feedback[2] : old('feedback.2')}}">
  <input class="form-control-sm form-control mb-1" placeholder="75% to 99%" name="feedback[]" value="{{! empty($quiz) ? $quiz->feedback[3] : old('feedback.3')}}">
  <input class="form-control-sm form-control mb-1" placeholder="100%" name="feedback[]" value="{{! empty($quiz) ? $quiz->feedback[4] : old('feedback.4')}}">
</div>