<?php

namespace App\Listeners\Emails;

use Illuminate\Mail\Events\MessageSending;

class EmbedLocalImages
{
    public function handle(MessageSending $event)
    {
        if (!app()->environment('local')) return;

        $root = preg_replace('#^https?://#i', '', config('app.url'));
        $host = strtolower((string) parse_url('//'.ltrim($root, '/'), PHP_URL_HOST));
        if (substr($host, -5) !== '.test') return;

        // Mailtrap cannot fetch .test URLs. Embed only publicly served bitmap
        // assets at delivery, leaving browser previews and production untouched.
        $embedded = [];
        $this->embedPart($event->message, $event->message, $host, $embedded);
    }

    private function embedPart($part, $message, $host, array &$embedded)
    {
        $children = $part->getChildren();
        if ($part->getBodyContentType() === 'text/html') {
            $html = preg_replace_callback('~(<img\b[^>]*?\s+src\s*=\s*)(["\'])(.*?)\2~is', function ($match) use ($message, $host, &$embedded) {
                $path = $this->imagePath(html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $host);
                if (!$path) return $match[0];

                if (!isset($embedded[$path])) {
                    $embedded[$path] = $message->embed(\Swift_Image::fromPath($path));
                }
                return $match[1].$match[2].$embedded[$path].$match[2];
            }, $part->getBody());
            $part->setBody($html);
        }
        foreach ($children as $child) $this->embedPart($child, $message, $host, $embedded);
    }

    private function imagePath($url, $siteHost)
    {
        $parts = parse_url($url);
        if (!$parts || (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true))) return;
        if (isset($parts['host'])) {
            $host = strtolower($parts['host']);
            if ($host !== $siteHost && substr($host, -strlen('.'.$siteHost)) !== '.'.$siteHost) return;
        }

        $path = '/'.ltrim(rawurldecode($parts['path'] ?? ''), '/');
        if (strpos($path, '/images/') !== 0 || strpos($path, "\0") !== false) return;
        $file = realpath(public_path(ltrim($path, '/')));
        $images = realpath(public_path('images'));
        if (!$file || !$images || strpos($file, $images.DIRECTORY_SEPARATOR) !== 0 || !is_file($file) || !is_readable($file)) return;

        // Reject traversal/symlink escapes and non-image files; never fetch URLs
        // or attach arbitrary local files referenced in rendered email content.
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true) || !@getimagesize($file)) return;

        return $file;
    }
}
