<?php

namespace App\Services;

class CampaignParameters
{
    const FIELDS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'];

    public static function fromQuery(array $query)
    {
        $parameters = [];
        foreach (self::FIELDS as $field) {
            $value = $query[$field] ?? '';
            // Campaign labels, rather than complete URLs, contact data or HTML.
            if (! is_string($value) || strlen($value) > 100) return null;
            $value = strtolower(trim($value));
            if ($value !== '' && ! preg_match('/^[a-z0-9][a-z0-9 ._-]*$/D', $value)) return null;
            $parameters[$field] = $value;
        }
        if ($parameters['utm_source'] === '' && $parameters['utm_medium'] === '' && $parameters['utm_campaign'] === '') return null;
        if ($parameters['utm_source'] !== '') {
            if (strpos($parameters['utm_source'], ' ') !== false) return null;
            $source = preg_replace('/^(?:www\.)+/', '', $parameters['utm_source']);
            if ($source === '') return null;
            // Compatibility aliases only: every other valid source works unchanged.
            $parameters['utm_source'] = ['youtube' => 'youtube.com', 'chatgpt' => 'chatgpt.com', 'reddit' => 'reddit.com'][$source] ?? $source;
        }
        return $parameters;
    }

    public static function key(array $parameters)
    {
        return hash('sha256', json_encode(array_map(function ($field) use ($parameters) {
            return $parameters[$field];
        }, self::FIELDS)));
    }
}
