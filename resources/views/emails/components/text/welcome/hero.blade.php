# Welcome, {{ $firstName }}

{{ trim(strip_tags(preg_replace('/<br\s*\/?>\s*/i', "\n", (string) $slot))) }}
