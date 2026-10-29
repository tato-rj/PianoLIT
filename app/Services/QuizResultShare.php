<?php

namespace App\Services;

use App\Quiz\Quiz;
use App\Support\LinkShare;
use Illuminate\Support\Facades\URL;

class QuizResultShare
{
    public function data(Quiz $quiz, int $score, int $total): array
    {
        abort_unless($quiz->published_at && $total > 0 && $total <= 999 && $score >= 0 && $score <= $total, 404);
        $parameters = ['quiz' => $quiz->slug, 'score' => $score, 'total' => $total];
        $url = URL::signedRoute('quizzes.share', $parameters);
        $title = trim(preg_replace('/\s+/u', ' ', strip_tags($quiz->title)), '. ');
        $challenge = $score === $total ? 'Can you match it?' : 'Can you beat it?';
        $headline = "I scored {$score}/{$total} on {$title}";
        $share = LinkShare::forMessage($url, $headline, $headline.' '.$challenge, 'My PianoLIT quiz score', $headline.' '.$challenge);
        $email = $share['destinations'][0];
        // Social destinations first; the shared component owns their presentation.
        $share['destinations'] = array_values(array_filter($share['destinations'], function ($destination) {
            return !in_array($destination['label'], ['Email', 'Text']);
        }));
        $share['destinations'][] = ['label' => 'X', 'brand' => 'x-twitter', 'url' => 'https://x.com/intent/tweet?'.http_build_query(['url' => $url, 'text' => $headline.' '.$challenge], '', '&', PHP_QUERY_RFC3986)];
        $share['destinations'][] = $email;

        return $share + [
            'image' => URL::signedRoute('quizzes.share-image', $parameters),
            'download' => URL::signedRoute('quizzes.share-image', $parameters + ['download' => 1]),
            'quiz_title' => $title, 'challenge' => $challenge, 'score' => $score, 'total' => $total,
        ];
    }

    public function image(array $share): string
    {
        // A fixed-size, local-only renderer: no user images, remote fetches or stored files.
        $image = imagecreatetruecolor(1200, 630);
        $color = function ($hex) use ($image) {
            return imagecolorallocate($image, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
        };
        $blue = $color('0055fe'); $white = $color('ffffff'); $yellow = $color('ffdc28');
        $ink = $color('172347'); $pink = $color('ff3e9d'); $mint = $color('00cfa2');
        imagefill($image, 0, 0, $blue);
        imagefilledellipse($image, 1150, 20, 360, 360, $pink);
        imagefilledellipse($image, 22, 642, 280, 280, $mint);
        imagefilledellipse($image, 927, 290, 388, 388, $yellow);
        imagesetthickness($image, 9);
        foreach ([[700, 65], [1110, 460], [650, 420], [80, 128]] as $i => $point) {
            [$x, $y] = $point;
            imageline($image, $x - 14, $y, $x + 14, $y, $i % 2 ? $mint : $pink);
            imageline($image, $x, $y - 14, $x, $y + 14, $i % 2 ? $mint : $pink);
        }
        $font = resource_path('fonts/OpenSans-ExtraBold.ttf');
        $regular = resource_path('fonts/OpenSans-SemiBold.ttf');
        $text = function ($label, $size, $x, $y, $fill, $face = null) use ($image, $font) {
            imagettftext($image, $size, 0, $x, $y, $fill, $face ?: $font, $label);
        };
        $width = function ($label, $size) use ($font) { $box = imagettfbbox($size, 0, $font, $label); return $box[2] - $box[0]; };
        $text('PianoLIT  /  THE QUIZ CHALLENGE', 23, 65, 80, $white, $regular);
        $text('I GOT', 26, 846, 193, $ink);
        $score = (string) $share['score'];
        $size = 126;
        while ($width($score, $size) > 295) $size -= 2;
        $text($score, $size, (int) (927 - $width($score, $size) / 2), 340, $ink);
        $outOf = 'OUT OF '.$share['total'];
        $text($outOf, 24, (int) (927 - $width($outOf, 24) / 2), 400, $ink);
        $words = preg_split('/\s+/u', mb_substr($share['quiz_title'], 0, 180));
        $lines = [''];
        foreach ($words as $word) {
            $index = count($lines) - 1;
            $candidate = ltrim($lines[$index].' '.$word);
            if ($width($candidate, 37) > 600 && $lines[$index] !== '') $lines[] = $word;
            else $lines[$index] = $candidate;
        }
        if (count($lines) > 3) $lines[2] .= '…';
        $lines = array_slice($lines, 0, 3);
        foreach ($lines as $i => $line) {
            while ($width($line, 37) > 600) $line = mb_substr($line, 0, -2).'…';
            $text($line, 37, 65, 205 + $i * 62, $white);
        }
        $text($share['challenge'], 31, 65, 439, $yellow);
        imagefilledrectangle($image, 65, 491, 599, 563, $white);
        $text('YOUR TURN. TAKE THE QUIZ', 23, 88, 538, $ink);
        $text('pianolit.com', 20, 893, 563, $white, $regular);
        ob_start();
        imagepng($image, null, 6);
        $bytes = ob_get_clean();
        imagedestroy($image);
        return $bytes;
    }
}
