<?php

namespace App\Services\Timeline;

use Illuminate\Support\Facades\{Cache, Http};
use Illuminate\Support\Str;

class WikimediaDiscovery
{
    const QUERY_ENDPOINT = 'https://query.wikidata.org/sparql';
    const WIKIPEDIA_ENDPOINT = 'https://en.wikipedia.org/w/api.php';
    const COMMONS_ENDPOINT = 'https://commons.wikimedia.org/w/api.php';

    public function pool(int $year, int $range): array
    {
        return Cache::remember('timeline.wikimedia.v3.'.$year.'.'.$range, now()->addMinutes(config('wikimedia.cache_minutes')), function () use ($year, $range) {
            $data = $this->get(self::QUERY_ENDPOINT, ['query' => $this->query($year, $range), 'format' => 'json']);
            if (!isset($data['results']['bindings']) || !is_array($data['results']['bindings'])) {
                throw new \RuntimeException('Invalid Wikidata response.');
            }
            $events = [];
            foreach ($data['results']['bindings'] as $row) {
                $value = function ($name, $default = '') use ($row) { return $row[$name]['value'] ?? $default; };
                $qid = basename($value('item'));
                $property = basename($value('property'));
                $date = substr($value('date'), 0, 10);
                $eventYear = (int) substr($date, 0, 4);
                $label = $value('itemLabel');
                if (!preg_match('/^Q[1-9][0-9]*$/', $qid) || !$label || $label === $qid || $eventYear < 1 || abs($eventYear - $year) > $range) continue;
                $kind = ['P571' => 'created', 'P577' => 'published', 'P585' => 'event', 'P580' => 'event', 'P569' => 'birth', 'P570' => 'death'][$property] ?? null;
                if (!$kind) continue;
                $category = $this->category($row);
                $sitelinks = (int) $value('sitelinks');
                if ($sitelinks < 15) continue;
                if (in_array($kind, ['birth', 'death']) ? ($category >= 5 && $sitelinks < 80) : $category > 5) continue;
                $worldEvent = $category === 5 && !in_array($kind, ['birth', 'death']);
                if ($property === 'P580' && !$worldEvent) continue;
                if ($worldEvent && $sitelinks < 30) continue;
                if (preg_match('/\b(skirmish|appointment)\b/i', $label)) continue;
                if (preg_match('/\b(battle|siege)\b/i', $label) && $sitelinks < 80) continue;
                // Use one identifier for an event's inception/start/date in the same year.
                if ($worldEvent) $kind = 'event';
                $sourceId = $qid.':'.(in_array($kind, ['created', 'published']) ? 'work' : $kind).':'.$eventYear;
                $suffix = ['created' => ' was created', 'published' => ' was published', 'event' => '', 'birth' => ' was born', 'death' => ' died'][$kind];
                $candidate = [
                    'source_id' => $sourceId, 'wikidata_id' => $qid, 'event_kind' => $kind, 'world_event' => $worldEvent,
                    'year' => $eventYear,
                    // Wikidata often stores January 1 for year-only dates. Respect precision.
                    'event_date' => (int) $value('precision', 9) >= 11 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null,
                    'title' => Str::limit($label.$suffix, 255, ''),
                    'description' => $value('itemDescription', $label),
                    'source_url' => $value('article'), 'article_title' => $this->articleTitle($value('article')),
                    'attribution' => 'Wikidata (CC0); Wikipedia contributors (CC BY-SA 4.0). Text may be edited by PianoLIT.',
                    'image_url' => null, 'image_source_url' => null, 'image_credit' => null,
                    'image_license' => null, 'image_license_url' => null,
                    'rank' => abs($eventYear - $year) * 3 + ($category - 1) * 7
                        + (in_array($kind, ['birth', 'death']) ? 8 : 0) - min(8, log(max(1, (int) $value('sitelinks')), 2)),
                ];
                if (!isset($events[$sourceId]) || $candidate['rank'] < $events[$sourceId]['rank']) $events[$sourceId] = $candidate;
            }
            return collect($events)->sortBy('rank')->values()->all();
        });
    }

    public function select(array $pool, int $limit = 10): array
    {
        $ranked = collect($pool)->sortBy('rank')->values();
        // Reserve three places for world context; fill unused places by cultural rank.
        $world = $ranked->filter(function ($event) { return !empty($event['world_event']); })->take(min(3, $limit));
        $ids = $world->pluck('source_id')->all();
        return $world->concat($ranked->reject(function ($event) use ($ids) {
            return in_array($event['source_id'], $ids, true);
        })->take($limit - $world->count()))->sortBy('rank')->values()->all();
    }

    public function identity(array $event): string
    {
        // Inception, publication and start dates can describe the same milestone.
        $kind = in_array($event['event_kind'], ['birth', 'death']) ? $event['event_kind'] : 'context';
        return $event['wikidata_id'].':'.$kind.':'.$event['year'];
    }

    public function enrich(array $events): array
    {
        if (!$events) return [];
        // Text/image enrichment is optional; dated Wikidata candidates still work if it fails.
        try {
            $data = $this->get(self::WIKIPEDIA_ENDPOINT, [
                'action' => 'query', 'format' => 'json', 'formatversion' => 2,
                'titles' => implode('|', array_unique(array_column($events, 'article_title'))),
                'redirects' => 1, 'prop' => 'extracts|pageimages|info', 'inprop' => 'url',
                'exintro' => 1, 'explaintext' => 1, 'exchars' => 450, 'exlimit' => 10,
                'piprop' => 'thumbnail|name', 'pithumbsize' => 480, 'pilicense' => 'free', 'pilimit' => 10,
            ]);
            $pages = collect($data['query']['pages'] ?? [])->keyBy('title');
            $aliases = collect(array_merge($data['query']['normalized'] ?? [], $data['query']['redirects'] ?? []))->pluck('to', 'from');
            $files = [];
            foreach ($events as &$event) {
                $title = $event['article_title'];
                for ($i = 0; $i < 3 && isset($aliases[$title]); $i++) $title = $aliases[$title];
                $page = $pages->get($title, []);
                if (!empty($page['extract'])) {
                    // Omit pronunciation/parenthetical digressions from the short card summary.
                    $summary = preg_replace('/\s*\([^()]*\)/u', '', $page['extract']);
                    $event['description'] = Str::limit(trim(preg_replace('/\s+/u', ' ', $summary)), 220);
                }
                if (!empty($page['pageimage']) && !empty($page['thumbnail']['source'])) {
                    $event['image_file'] = 'File:'.str_replace('_', ' ', $page['pageimage']);
                    $event['thumbnail'] = $page['thumbnail']['source'];
                    $files[] = $event['image_file'];
                }
            }
            unset($event);
            if ($files) {
                $images = $this->get(self::COMMONS_ENDPOINT, [
                    'action' => 'query', 'format' => 'json', 'formatversion' => 2,
                    'titles' => implode('|', array_unique($files)), 'redirects' => 1, 'prop' => 'imageinfo',
                    'iiprop' => 'url|extmetadata', 'iiurlwidth' => 480,
                    'iiextmetadatafilter' => 'Artist|Credit|LicenseShortName|LicenseUrl|UsageTerms',
                ]);
                $imageAliases = collect(array_merge($images['query']['normalized'] ?? [], $images['query']['redirects'] ?? []))->pluck('to', 'from');
                $images = collect($images['query']['pages'] ?? [])->keyBy('title');
                foreach ($events as &$event) {
                    $file = $event['image_file'] ?? '';
                    for ($i = 0; $i < 3 && isset($imageAliases[$file]); $i++) $file = $imageAliases[$file];
                    $info = $images->get($file, [])['imageinfo'][0] ?? [];
                    $meta = $info['extmetadata'] ?? [];
                    if (empty($info['descriptionurl']) || empty($meta['LicenseShortName']['value'])) continue;
                    $event['image_url'] = $info['thumburl'] ?? $event['thumbnail'] ?? null;
                    $event['image_source_url'] = $info['descriptionurl'];
                    $event['image_credit'] = Str::limit($this->plain(($meta['Artist']['value'] ?? '').' '.($meta['Credit']['value'] ?? '')), 2000);
                    $event['image_license'] = Str::limit($this->plain($meta['LicenseShortName']['value']), 255, '');
                    $event['image_license_url'] = $this->safeUrl($meta['LicenseUrl']['value'] ?? null);
                }
                unset($event);
            }
        } catch (\Throwable $e) {
            // Never present uncredited images when Commons metadata is unavailable.
        }
        return array_map(function ($event) {
            foreach (['source_url', 'image_url', 'image_source_url', 'image_license_url'] as $field) $event[$field] = $this->safeUrl($event[$field]);
            unset($event['article_title'], $event['rank'], $event['world_event'], $event['image_file'], $event['thumbnail']);
            return $event;
        }, $events);
    }

    private function get($endpoint, array $params): array
    {
        $response = Http::withHeaders(['User-Agent' => config('wikimedia.user_agent'), 'Accept' => 'application/json'])
            ->withOptions(['connect_timeout' => 3])->timeout(config($endpoint === self::QUERY_ENDPOINT ? 'wikimedia.query_timeout' : 'wikimedia.timeout'))->get($endpoint, $params)->throw();
        $data = $response->json();
        if (!is_array($data) || isset($data['error'])) throw new \RuntimeException('Wikimedia unavailable.');
        return $data;
    }

    private function articleTitle($url)
    {
        return str_replace('_', ' ', rawurldecode(substr(parse_url($url, PHP_URL_PATH) ?: '', 6)));
    }

    private function plain($text)
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));
    }

    private function safeUrl($url)
    {
        if ($url && strpos($url, '//') === 0) $url = 'https:'.$url;
        return $url && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https' ? $url : null;
    }

    private function category(array $row): int
    {
        if (isset($row['category']['value'])) return (int) $row['category']['value'];
        $roots = [
            'Q2188189' => 1, // musical work
            'Q838948' => 2, // work of art
            'Q7725634' => 3, // literary work
            'Q52260246' => 4, // scientific event
            'Q14208553' => 4, // invention
            'Q13418847' => 5, // historical event
            'Q10931' => 5, // revolution
            'Q198' => 5, // war
            'Q131569' => 5, // treaty
            'Q124734' => 5, // rebellion
        ];
        $occupations = ['Q36834' => 1, 'Q639669' => 1, 'Q483501' => 2, 'Q36180' => 3, 'Q49757' => 3, 'Q901' => 4];
        $category = 6;
        foreach (['class'] as $field) {
            $category = min($category, $roots[basename($row[$field]['value'] ?? '')] ?? 6);
        }
        foreach (['occupation'] as $field) {
            $category = min($category, $occupations[basename($row[$field]['value'] ?? '')] ?? 6);
        }
        // English Wikidata descriptions help recognize specific work/occupation types
        // without recursively traversing the whole taxonomy on the public query service.
        $description = $row['itemDescription']['value'] ?? '';
        foreach ([
            1 => '/\b(composer|musician|pianist|organist|opera|oratorio|cantata|sonata|symphony|concerto|musical composition|musical work|ballet)\b/i',
            2 => '/\b(painter|sculptor|artist|painting|sculpture|artwork|work of art)\b/i',
            3 => '/\b(writer|poet|novelist|novel|poem|literary work|playwright)\b/i',
            4 => '/\b(scientist|physicist|chemist|mathematician|astronomer|inventor|invention|discovery|scientific event)\b/i',
            5 => '/\b(revolution|historical event|war|armed conflict|battle|siege|treaty|rebellion|uprising|independence|expedition|circumnavigation|earthquake|tsunami|volcanic eruption|famine|pandemic|epidemic|abolition|coronation)\b/i',
        ] as $priority => $pattern) {
            if (preg_match($pattern, $description)) $category = min($category, $priority);
        }
        return $category;
    }

    public function query(int $year, int $range): string
    {
        $start = sprintf('%04d-01-01T00:00:00Z', max(1, $year - $range));
        $end = sprintf('%04d-12-31T23:59:59Z', min(9999, $year + $range));
        // Independent dated pools keep buildings/publications from crowding out births,
        // deaths and historical events. Start dates include wars and expeditions that
        // have no single point-in-time value. Date-first execution avoids broad joins.
        $branches = [];
        foreach (['P571', 'P577', 'P585', 'P580', 'P569', 'P570'] as $property) {
            $branches[] = '{ SELECT ?item ?date ?article ?sitelinks ?property WHERE {
'
                .'hint:Query hint:optimizer "None" .
'
                .'?item wdt:'.$property.' ?date . hint:Prior hint:rangeSafe true .
'
                .'FILTER(?date >= "'.$start.'"^^xsd:dateTime && ?date <= "'.$end.'"^^xsd:dateTime)
'
                .'?item wikibase:sitelinks ?sitelinks . FILTER(?sitelinks >= 15)
'
                .'?article schema:about ?item; schema:isPartOf <https://en.wikipedia.org/> .
'
                .'BIND(wdt:'.$property.' AS ?property)
'
                .'} ORDER BY DESC(?sitelinks) ASC(?date) ASC(?item) LIMIT 50 }';
        }
        $dates = implode(" UNION ", $branches);
        return <<<SPARQL
PREFIX wd: <http://www.wikidata.org/entity/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX p: <http://www.wikidata.org/prop/>
PREFIX psv: <http://www.wikidata.org/prop/statement/value/>
PREFIX wikibase: <http://wikiba.se/ontology#>
PREFIX schema: <http://schema.org/>
PREFIX hint: <http://www.bigdata.com/queryHints#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
SELECT ?item ?itemLabel ?itemDescription ?property ?date ?precision ?article ?sitelinks ?class ?occupation WHERE {
  hint:Query hint:optimizer "None" .
  { $dates }
  OPTIONAL { ?item wdt:P31 ?class . }
  OPTIONAL { ?item wdt:P106 ?occupation . }
  VALUES (?property ?claim ?valueProperty) {
    (wdt:P571 p:P571 psv:P571) (wdt:P577 p:P577 psv:P577) (wdt:P585 p:P585 psv:P585)
    (wdt:P580 p:P580 psv:P580)
    (wdt:P569 p:P569 psv:P569) (wdt:P570 p:P570 psv:P570)
  }
  OPTIONAL { ?item ?claim ?statement . ?statement ?valueProperty ?node . ?node wikibase:timeValue ?date; wikibase:timePrecision ?precision . }
  SERVICE wikibase:label { <http://www.bigdata.com/rdf#serviceParam> wikibase:language "en" . }
}
SPARQL;
    }
}
