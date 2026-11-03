<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class FeedbackMessage implements Rule
{
    public function passes($attribute, $value)
    {
        // Inspect a decoded copy only. Never strip a spam link and save the remaining advert.
        $text = $value;
        for ($i = 0; $i < 3; $i++) {
            $decoded = html_entity_decode(rawurldecode($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $text) break;
            $text = $decoded;
        }
        if (!mb_check_encoding($text, 'UTF-8')) return false;
        $text = mb_convert_kana($text, 'as', 'UTF-8');
        $text = str_replace(["\u{3002}", "\u{FF61}", "\u{2024}"], '.', $text);
        $text = preg_replace('/[\p{Cf}\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        $patterns = [
            // Feedback is plain text, including when markup was entity-encoded.
            '~<\s*/?\s*[a-z][^>]*>|<!--~iu',
            '~\[\s*/?\s*(?:url|link|img)\b[^\]]*\]~iu',
            '~\b[a-z][a-z0-9+.-]*\s*:\s*/\s*/|\bwww\s*\.~iu',
            // Bare domains (including IDNs) and IP addresses need no scheme to be spam links.
            '~(?<![\p{L}\p{N}_-])(?:[\p{L}\p{N}](?:[\p{L}\p{N}-]{0,61}[\p{L}\p{N}])?\.)+(?:[\p{L}]{2,63}|xn--[a-z0-9-]{2,59})(?![\p{L}\p{N}_-])~iu',
            '~(?<![\d.])(?:\d{1,3}\.){3}\d{1,3}(?![\d.])~u',
        ];
        foreach ($patterns as $pattern) {
            // A decoding/Unicode failure must not bypass the check.
            if (preg_match($pattern, $text) !== 0) return false;
        }

        return true;
    }

    public function message()
    {
        return 'Please send your feedback as plain text without website links or HTML.';
    }
}
