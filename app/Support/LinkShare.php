<?php

namespace App\Support;

class LinkShare
{
    public static function forPage(string $url, string $title): array
    {
        $parts = parse_url($url);
        if (!$parts || !in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new \InvalidArgumentException('Sharing requires an absolute public page URL.');
        }
        $title = trim(str_replace(["\r", "\n"], ' ', strip_tags($title)));
        $note = "Hi!\n\nI found {$title} on PianoLIT and thought you might enjoy it.";
        $message = $note."\n\n".$url;
        $query = function (array $values) { return http_build_query($values, '', '&', PHP_QUERY_RFC3986); };

        return [
            'url' => $url, 'title' => $title, 'message' => $message,
            'destinations' => [
                ['label' => 'Email', 'icon' => 'mail', 'url' => 'mailto:?'.$query(['subject' => 'Shared piece from PianoLIT', 'body' => str_replace("\n", "\r\n", $message)])],
                ['label' => 'Text', 'icon' => 'message-circle', 'url' => 'sms:?'.$query(['body' => $message]), 'sms' => true],
                ['label' => 'WhatsApp', 'brand' => 'whatsapp', 'url' => 'https://wa.me/?'.$query(['text' => $message])],
                ['label' => 'Facebook', 'brand' => 'facebook-f', 'url' => 'https://www.facebook.com/sharer/sharer.php?'.$query(['u' => $url])],
                ['label' => 'Reddit', 'brand' => 'reddit', 'url' => 'https://www.reddit.com/submit?'.$query(['url' => $url, 'title' => 'A piece I found on PianoLIT: '.$title])],
                ['label' => 'Telegram', 'brand' => 'telegram', 'url' => 'https://t.me/share/url?'.$query(['url' => $url, 'text' => $note])],
            ],
        ];
    }
}
