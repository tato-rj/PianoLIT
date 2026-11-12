<?php

namespace App\Services;

use App\Quiz\Quiz;

class QuizPageContent
{
    public function for(Quiz $quiz): array
    {
        $title = trim($quiz->title, ". \t\r\n");
        $description = trim((string) $quiz->description);
        $edition = config('quiz-search.pages.'.$quiz->slug);

        // An editor's later changes take precedence over the audited web copy.
        if ($edition && $edition['source'] === hash('sha256', $title."\n".$description)) {
            return ['title' => $edition['title'], 'description' => $edition['description']];
        }

        return compact('title', 'description');
    }
}
