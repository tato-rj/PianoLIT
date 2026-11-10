@php
    $selectedAnswer = $correctAnswers[$questionIndex] ?? null;
    if (!is_scalar($selectedAnswer)) $selectedAnswer = null;
    if ($selectedAnswer === null) {
        foreach ($row as $key => $answer) {
            if ($key != 0 && is_string($answer) && strpos($answer, '[x]') !== false) $selectedAnswer = $key;
        }
    }
@endphp
<fieldset class="border-top pt-3" data-quiz-question data-question-index="{{ $questionIndex }}">
    <legend class="visually-hidden" data-question-legend>Question {{ $number }}</legend>
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <h3 class="h5 mb-0" data-question-number>Question {{ $number }}</h3>
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'label' => 'Remove question '.$number, 'size' => 'xs', 'attributes' => ['data-remove-question' => true]]])
    </div>
    @input(['bag' => 'default', 'id' => 'quiz-question-'.$questionIndex, 'name' => 'questions['.$questionIndex.'][0]', 'label' => 'Question', 'placeholder' => 'Enter a question', 'value' => is_scalar($row[0] ?? '') ? $row[0] : '', 'useOldInput' => false, 'errorKey' => 'questions.'.$questionIndex.'.0', 'required' => true])
    <div class="row g-3">
        @for($answerIndex = 1; $answerIndex <= max(4, count($row) - 1); $answerIndex++)
        <div class="col-md-6">
            @input(['bag' => 'default', 'id' => 'quiz-answer-'.$questionIndex.'-'.$answerIndex, 'name' => 'questions['.$questionIndex.']['.$answerIndex.']', 'label' => 'Answer '.$answerIndex, 'placeholder' => 'Enter an answer', 'value' => is_scalar($row[$answerIndex] ?? '') ? str_replace('[x]', '', $row[$answerIndex] ?? '') : '', 'useOldInput' => false, 'errorKey' => 'questions.'.$questionIndex.'.'.$answerIndex, 'required' => false, 'grid' => 'mb-2'])
            @include('components.forms.checkbox', ['type' => 'radio', 'id' => 'quiz-correct-'.$questionIndex.'-'.$answerIndex, 'name' => 'correct_answers['.$questionIndex.']', 'value' => $answerIndex, 'label' => 'Answer '.$answerIndex.' is correct', 'checked' => (string) $selectedAnswer === (string) $answerIndex, 'classes' => 'mb-0'])
        </div>
        @endfor
    </div>
    @include('components.forms.error', ['field' => 'questions.'.$questionIndex, 'errorClasses' => 'd-block'])
    @include('components.forms.error', ['field' => 'correct_answers.'.$questionIndex, 'errorClasses' => 'd-block'])
</fieldset>
