<?php

namespace App\Activity;

use Illuminate\Http\Request;

class TrafficFilter
{
    public static function minimumMilliseconds()
    {
        $seconds = filter_var(config('activity.minimum_visit_seconds', 5), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 60]]);
        return ($seconds === false ? 5 : $seconds) * 1000;
    }

    public static function ignores(Request $request)
    {
        if (!config('activity.filter_bots', true)) return false;
        if ($request->isMethod('HEAD')) return true;
        $agent = substr((string) $request->userAgent(), 0, 2048);
        // Analytics exclusion only: never use a self-declared agent as authorization
        // or block crawlers from accessing/indexing the actual website.
        return (bool) preg_match('~googlebot|googleother|google-inspectiontool|adsbot-google|mediapartners-google|feedfetcher-google|bingbot|msnbot|duckduckbot|yandexbot|baiduspider|petalbot|applebot|semrushbot|ahrefsbot|dotbot|mj12bot|sogou|slurp|facebookexternalhit|facebot|twitterbot|linkedinbot|slackbot|discordbot|telegrambot|pinterestbot|gptbot|oai-searchbot|chatgpt-user|claudebot|claude-web|anthropic-ai|amazonbot|ccbot|bytespider|perplexitybot|dataforseobot|headlesschrome|phantomjs|selenium|playwright|(?:^|[ /;(])(?:bot|crawler|spider)(?:[/; )]|$)|^(?:curl|wget|python-requests|python-urllib|go-http-client|libwww-perl|scrapy|httpclient)/~i', $agent);
    }
}
