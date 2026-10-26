<?php

namespace App\Services;

class FreePickCampaignLinks
{
    const TAGS = ['utm_source' => 'pianolit', 'utm_medium' => 'email', 'utm_campaign' => 'freepick'];

    private $enabled;

    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public static function parameters(string $placement): array
    {
        return self::TAGS + ['utm_content' => $placement];
    }

    public function url(string $url, string $placement): string
    {
        if (!$this->enabled || !preg_match('#^https?://#i', $url)) return $url;
        [$url, $fragment] = array_pad(explode('#', $url, 2), 2, null);
        [$base, $query] = array_pad(explode('?', $url, 2), 2, '');
        parse_str($query, $parameters);
        $parameters = array_replace($parameters, self::parameters($placement));

        return $base.'?'.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986)
            .($fragment !== null ? '#'.$fragment : '');
    }
}
