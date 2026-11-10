@include('admin.components.table-action', ['tableAction' => [
    'label' => 'Preview '.$previewQuiz->title, 'icon' => 'eye', 'iconOnly' => true,
    'url' => $previewQuiz->published_at ? route('quizzes.show', $previewQuiz) : null,
    'attributes' => [
        'target' => $previewQuiz->published_at ? '_blank' : null,
        'rel' => $previewQuiz->published_at ? 'noopener noreferrer' : null,
        'disabled' => !$previewQuiz->published_at,
        'aria-disabled' => $previewQuiz->published_at ? null : 'true',
        'title' => $previewQuiz->published_at ? 'Preview '.$previewQuiz->title : 'Publish this quiz to view it.',
    ],
]])
