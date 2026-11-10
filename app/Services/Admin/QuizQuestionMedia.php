<?php

namespace App\Services\Admin;

use App\Quiz\Quiz;

class QuizQuestionMedia
{
    public function split(string $question): array
    {
        if (preg_match('/\[(.*?)\]/', $question, $match, PREG_OFFSET_CAPTURE)) {
            $value = $match[1][0];
            if (strpos($value, 'audio') !== false || strpos($value, 'images') !== false) {
                return ['text' => trim(substr_replace($question, '', $match[0][1], strlen($match[0][0]))), 'media' => $value];
            }
        }
        return ['text' => $question, 'media' => ''];
    }

    public function diskPath(string $value): ?string
    {
        return preg_match('#\A/storage/(quiz/(?:audio|images)/[^\[\]]+)\z#D', $value, $match) ? rawurldecode($match[1]) : null;
    }

    public function reference(string $path): string
    {
        return '/storage/'.implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    public function selection(string $value): ?array
    {
        if ($value === '') return null;
        // Saved legacy URLs retain their exact value, but previews use only an owned local file.
        $path = parse_url($value, PHP_URL_PATH);
        $diskPath = is_string($path) ? $this->diskPath('/'.ltrim($path, '/')) : null;
        $available = $diskPath && app(QuizMedia::class)->exists($diskPath);
        return [
            'value' => $value,
            'type' => $diskPath ? (strpos($diskPath, 'quiz/audio/') === 0 ? 'audio' : 'images') : (strpos($value, 'audio') !== false ? 'audio' : 'images'),
            'name' => basename(rawurldecode(is_string($path) ? $path : $value)),
            'url' => $available ? asset($this->reference($diskPath)) : null,
        ];
    }

    public function allowed(string $value, ?Quiz $quiz, $index): bool
    {
        if ($value === '') return true;
        if ($quiz && isset($quiz->questions[$index]) && $this->split($quiz->questions[$index]['Q'])['media'] === $value) return true;
        $path = $this->diskPath($value);
        return $path !== null && $value === $this->reference($path) && app(QuizMedia::class)->exists($path);
    }

    public function join(string $text, string $media): string
    {
        if ($media === '') return $text;
        // The public and mobile parser reads the first bracketed value.
        return strpos($text, '[') === false ? rtrim($text).' ['.$media.']' : '['.$media.'] '.ltrim($text);
    }
}
