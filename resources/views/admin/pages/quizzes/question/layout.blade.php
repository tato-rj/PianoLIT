@component('admin.components.editor.section', ['id' => 'quiz-questions', 'sectionId' => 'quiz-questions', 'title' => 'Questions', 'description' => 'Enter at least two answers for each question and select the correct answer.'])
    @include('components.forms.error', ['field' => 'questions', 'errorClasses' => 'd-block'])
    <div class="d-flex flex-column gap-4" data-quiz-questions>
        @foreach($questionRows as $index => $row)
            @if(is_array($row) && ctype_digit((string) $index))
                @include('admin.pages.quizzes.question.input', ['questionIndex' => $index, 'row' => $row, 'number' => $loop->iteration])
            @endif
        @endforeach
    </div>
    <template data-quiz-question-template>
        @include('admin.pages.quizzes.question.input', ['questionIndex' => '__INDEX__', 'row' => ['', '', '', '', ''], 'number' => 1, 'correctAnswers' => [], 'questionMedia' => [], 'separateMedia' => true])
    </template>
    <button type="button" class="btn btn-secondary btn-sm mt-3" data-add-question>@icon('plus')Add question</button>
    <noscript><p class="small text-muted mt-2 mb-0">Enable JavaScript to add or remove questions.</p></noscript>
@endcomponent
