<?php

namespace App\Services\Shop;

/** Keep literal supplier composition statements, including color/size qualifiers. */
class MaterialDescription
{
    const MAX_LENGTH = 6000;

    public static function extract($description): ?string
    {
        // null means unavailable metadata; an empty string means no material statement.
        if (!is_string($description) || strlen($description) > 60000 || !mb_check_encoding($description, 'UTF-8')
            || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $description)) return null;
        $description = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#isu', '', $description);
        $description = preg_replace('#<li\b[^>]*>#iu', "\n• ", $description);
        $description = preg_replace('#</(?:li|p|div)>|<br\s*/?>#iu', "\n", $description);
        $description = html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $description)) return null;

        $items = [];
        $current = null;
        foreach (preg_split('/\R/u', $description) as $line) {
            $line = trim($line);
            if (preg_match('/^[•●▪*\-]\s*(.+)$/u', $line, $match)) {
                if ($current !== null) $items[] = ['text' => $current, 'listed' => true];
                $current = $match[1];
            } elseif ($current !== null && $line !== '') {
                $current .= ' '.$line;
            } else {
                if ($current !== null) $items[] = ['text' => $current, 'listed' => true];
                $current = null;
                if ($line !== '') $items[] = ['text' => $line, 'listed' => false];
            }
        }
        if ($current !== null) $items[] = ['text' => $current, 'listed' => true];

        $fibers = 'cotton|polyester|viscose|rayon|nylon|polyamide|elastane|spandex|acrylic|wool|linen|silk|hemp|bamboo|lyocell|modal|tencel|acetate|leather|polyurethane|polypropylene';
        $materials = [];
        $hasStatement = false;
        foreach ($items as $entry) {
            $item = $entry['text'];
            // Full introductory/marketing paragraphs are not material details.
            if (mb_strlen($item) > 800) continue;
            $standalone = preg_match('/^(?:(?:materials?|fabric(?: composition)?|composition|fiber content|shell|lining)\s*:\s*)?\d+(?:[.,]\d+)?\s*%/iu', $item);
            $statement = ($entry['listed'] || $standalone) && preg_match('/\d+(?:[.,]\d+)?\s*%[^%\n]{0,80}\b(?:'.$fibers.')\b/iu', $item);
            $qualifier = ($entry['listed'] || preg_match('/^(?:note|disclaimer|fabric|fib(?:er|re)|material|composition|heather|solid|color)\b/iu', $item))
                && preg_match('/\b(?:'.$fibers.'|fabric|fib(?:er|re)|materials?|composition)\b/iu', $item)
                && preg_match('/\b(?:vary|varies|depending|except|excluding|colors?|sizes?|blends?)\b/iu', $item);
            if (!$statement && !$qualifier) continue;
            if ($statement) $hasStatement = true;
            // Preserve the exact statement and qualifiers, rather than inventing variant percentages.
            $materials[] = trim(preg_replace('/[\t ]+/u', ' ', $item));
        }
        if (!$hasStatement) return '';
        $materials = array_values(array_unique($materials));
        if (count($materials) > 20) return null;
        $text = implode("\n", $materials);
        return mb_strlen($text) <= self::MAX_LENGTH ? $text : null;
    }
}
