@foreach($quiz->questions() as $question)
<section class="quiz-item border rounded-sm p-4 mb-3" data-quiz-question aria-labelledby="quiz-question-{{ $loop->iteration }}" {{ $loop->first ? '' : 'hidden' }}>
    <p class="small text-muted mb-2">Question {{ $loop->iteration }} of {{ $loop->count }}</p>
    <h2 class="h5 mb-4" id="quiz-question-{{ $loop->iteration }}" tabindex="-1">{{ rtrim($question['Q'], '. ') }}</h2>
    @if($question['audio'])
        <audio controls preload="none" class="w-100 d-block mb-4" aria-label="Audio for question {{ $loop->iteration }}"><source src="{{ asset($question['audio']) }}" type="audio/mp3"></audio>
    @endif
    @if($question['image'])
        <img src="{{ asset($question['image']) }}" class="quiz-question-image d-block mb-4" alt="Illustration for question {{ $loop->iteration }}" loading="lazy">
    @endif
    <div class="quiz-answers d-grid gap-2" role="group" aria-labelledby="quiz-question-{{ $loop->iteration }}">
        @foreach($question['A'] as $answer)
            <button type="button" class="btn btn-secondary quiz-answer text-start w-100" data-quiz-answer="{{ $loop->index }}" aria-pressed="false" {{ strhas($answer, '[x]') ? 'correct' : '' }}>
                <span class="quiz-answer-letter" aria-hidden="true">{{ chr(65 + $loop->index) }}</span>
                <span class="quiz-answer-copy">{{ str_replace('[x]', '', $answer) }}</span>
                <span class="quiz-answer-outcome small" data-quiz-answer-outcome hidden></span>
            </button>
        @endforeach
    </div>
    <p class="quiz-reaction mb-0 mt-3" data-quiz-question-feedback role="status" aria-live="polite" aria-atomic="true"></p>
</section>
@endforeach
