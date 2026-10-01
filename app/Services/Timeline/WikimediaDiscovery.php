<?php

namespace App\Services\Timeline;

use Illuminate\Support\Facades\{Cache, Log};
use Illuminate\Support\Str;

class WikimediaDiscovery
{
    const QUERY_ENDPOINT = 'https://query.wikidata.org/sparql';
    const ENTITY_ENDPOINT = 'https://www.wikidata.org/w/api.php';
    const PROPERTIES = ['P571', 'P577', 'P585', 'P580', 'P569', 'P570'];
    const WIKIPEDIA_ENDPOINT = 'https://en.wikipedia.org/w/api.php';
    const COMMONS_ENDPOINT = 'https://commons.wikimedia.org/w/api.php';

    private $client;
    private $deadline;

    public function __construct(?WikimediaClient $client = null)
    {
        $this->client = $client ?: new WikimediaClient;
    }

    public function pool(int $year, int $range, int $batch = 0): array
    {
        return $this->batch($year, $range, $batch)['events'];
    }

    public function batch(int $year, int $range, int $batch = 0): array
    {
        $key = 'timeline.wikimedia.v4.'.$year.'.'.$range.'.'.$batch;
        if ($cached = Cache::get($key)) return $cached;
        $raw = [];
        $complete = true;
        $hasMore = false;
        foreach (self::PROPERTIES as $property) {
            try {
                $rows = Cache::remember($key.'.'.$property, now()->addMinutes(config('wikimedia.cache_minutes')), function () use ($year, $range, $property, $batch) {
                    $data = $this->get(self::QUERY_ENDPOINT, ['query' => $this->query($year, $range, $property, $batch), 'format' => 'json']);
                    if (!isset($data['results']['bindings']) || !is_array($data['results']['bindings'])) {
                        Log::warning('Invalid timeline Wikidata response', ['endpoint' => self::QUERY_ENDPOINT, 'http_status' => 200, 'property' => $property]);
                        throw new \RuntimeException('Invalid Wikidata response.');
                    }
                    return $data['results']['bindings'];
                });
            } catch (\Throwable $e) {
                $complete = false;
                continue;
            }
            $hasMore = $hasMore || count($rows) >= config('wikimedia.candidate_limit');
            $dated = [];
            foreach ($rows as $row) {
                $qid = basename($row['item']['value'] ?? '');
                $date = substr($row['date']['value'] ?? '', 0, 10);
                $eventYear = (int) substr($date, 0, 4);
                $sitelinks = (int) ($row['sitelinks']['value'] ?? 0);
                if (!preg_match('/^Q[1-9][0-9]*$/', $qid) || $eventYear < 1 || abs($eventYear - $year) > $range || $sitelinks < 15) continue;
                $dated[$qid.':'.$date] = [
                    'wikidata_id' => $qid, 'property' => $property, 'date' => $date,
                    'year' => $eventYear, 'sitelinks' => $sitelinks,
                    'rank' => abs($eventYear - $year) * 3 - min(8, log($sitelinks, 2)),
                ];
            }
            // Shortlist separately so works and world events survive highly notable births.
            $raw = array_merge($raw, collect($dated)->sortBy('rank')->take(config('wikimedia.shortlist_per_property'))->values()->all());
        }
        if (!$complete && !$raw) throw new \RuntimeException('Wikidata discovery unavailable.');
        $entities = $this->entities(array_unique(array_column($raw, 'wikidata_id')));
        $events = [];
        foreach ($raw as $item) {
            $entity = $entities[$item['wikidata_id']] ?? [];
            $candidate = $this->candidate($item, $entity);
            if (!$candidate) continue;
            $identity = $this->identity($candidate);
            if (!isset($events[$identity]) || $candidate['rank'] < $events[$identity]['rank']) $events[$identity] = $candidate;
        }
        $result = [
            'events' => collect($events)->sortBy('rank')->values()->all(), 'complete' => $complete,
            'has_more' => (!$complete || $hasMore) && $batch + 1 < config('wikimedia.max_batches'),
        ];
        // Partial results work, but failed properties remain retryable on the next request.
        if ($complete) Cache::put($key, $result, now()->addMinutes(config('wikimedia.cache_minutes')));
        return $result;
    }

    private function entities(array $ids): array
    {
        $entities = [];
        $missing = [];
        foreach ($ids as $id) {
            $cached = Cache::get('timeline.wikimedia.entity.v1.'.$id);
            if ($cached !== null) $entities[$id] = $cached;
            else $missing[] = $id;
        }
        foreach (array_chunk($missing, 50) as $chunk) {
            $data = $this->get(self::ENTITY_ENDPOINT, [
                'action' => 'wbgetentities', 'format' => 'json', 'ids' => implode('|', $chunk),
                'props' => 'labels|descriptions|claims|sitelinks', 'languages' => 'en', 'sitefilter' => 'enwiki', 'maxlag' => 5,
            ]);
            if (!isset($data['entities']) || !is_array($data['entities'])) {
                Log::warning('Invalid timeline Wikidata entity response', ['endpoint' => self::ENTITY_ENDPOINT, 'http_status' => 200]);
                throw new \RuntimeException('Invalid Wikidata entity response.');
            }
            foreach ($chunk as $id) {
                $entities[$id] = $data['entities'][$id] ?? [];
                Cache::put('timeline.wikimedia.entity.v1.'.$id, $entities[$id], now()->addMinutes(config('wikimedia.cache_minutes')));
            }
        }
        return $entities;
    }

    private function candidate(array $item, array $entity): ?array
    {
        $label = $entity['labels']['en']['value'] ?? '';
        $description = $entity['descriptions']['en']['value'] ?? $label;
        $article = $entity['sitelinks']['enwiki']['title'] ?? '';
        if (!$label || !$article) return null;
        $row = ['itemDescription' => ['value' => $description]];
        $category = $this->category($row);
        foreach (['P31' => 'class', 'P106' => 'occupation'] as $property => $field) {
            foreach ($entity['claims'][$property] ?? [] as $claim) {
                $row[$field] = ['value' => $claim['mainsnak']['datavalue']['value']['id'] ?? ''];
                $category = min($category, $this->category($row));
            }
        }
        $kind = ['P571' => 'created', 'P577' => 'published', 'P585' => 'event', 'P580' => 'event', 'P569' => 'birth', 'P570' => 'death'][$item['property']];
        if (in_array($kind, ['birth', 'death']) ? ($category >= 5 && $item['sitelinks'] < 80) : $category > 5) return null;
        $worldEvent = $category === 5 && !in_array($kind, ['birth', 'death']);
        if ($item['property'] === 'P580' && !$worldEvent) return null;
        if ($worldEvent && $item['sitelinks'] < 30) return null;
        if (preg_match('/\b(skirmish|appointment)\b/i', $label)) return null;
        if (preg_match('/\b(battle|siege)\b/i', $label) && $item['sitelinks'] < 80) return null;
        if ($worldEvent) $kind = 'event';
        $eventDate = null;
        foreach ($entity['claims'][$item['property']] ?? [] as $claim) {
            if (($claim['rank'] ?? '') === 'deprecated') continue;
            $value = $claim['mainsnak']['datavalue']['value'] ?? [];
            $date = ltrim(substr($value['time'] ?? '', 0, 11), '+');
            if ($date === $item['date'] && ($value['precision'] ?? 9) >= 11 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $eventDate = $date;
                break;
            }
        }
        $suffix = ['created' => ' was created', 'published' => ' was published', 'event' => '', 'birth' => ' was born', 'death' => ' died'][$kind];
        return [
            'source_id' => $item['wikidata_id'].':'.(in_array($kind, ['created', 'published']) ? 'work' : $kind).':'.$item['year'],
            'wikidata_id' => $item['wikidata_id'], 'event_kind' => $kind, 'world_event' => $worldEvent,
            'year' => $item['year'], 'event_date' => $eventDate, 'title' => Str::limit($label.$suffix, 255, ''),
            'description' => $description,
            'source_url' => 'https://en.wikipedia.org/wiki/'.rawurlencode(str_replace(' ', '_', $article)),
            'article_title' => $article,
            'attribution' => 'Wikidata (CC0); Wikipedia contributors (CC BY-SA 4.0). Text may be edited by PianoLIT.',
            'image_url' => null, 'image_source_url' => null, 'image_credit' => null,
            'image_license' => null, 'image_license_url' => null,
            'rank' => $item['rank'] + ($category - 1) * 7 + (in_array($kind, ['birth', 'death']) ? 8 : 0),
        ];
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
            $pages = $this->pages(self::WIKIPEDIA_ENDPOINT, array_unique(array_column($events, 'article_title')), [
                'action' => 'query', 'format' => 'json', 'formatversion' => 2,
                'redirects' => 1, 'prop' => 'extracts|pageimages|info', 'inprop' => 'url',
                'exintro' => 1, 'explaintext' => 1, 'exchars' => 450, 'exlimit' => 10,
                'piprop' => 'thumbnail|name', 'pithumbsize' => 480, 'pilicense' => 'free', 'pilimit' => 10, 'maxlag' => 5,
            ]);
            $files = [];
            foreach ($events as &$event) {
                $page = $pages[$event['article_title']] ?? [];
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
                $images = $this->pages(self::COMMONS_ENDPOINT, array_unique($files), [
                    'action' => 'query', 'format' => 'json', 'formatversion' => 2,
                    'redirects' => 1, 'prop' => 'imageinfo', 'maxlag' => 5,
                    'iiprop' => 'url|extmetadata', 'iiurlwidth' => 480,
                    'iiextmetadatafilter' => 'Artist|Credit|LicenseShortName|LicenseUrl|UsageTerms',
                ]);
                foreach ($events as &$event) {
                    $file = $event['image_file'] ?? '';
                    $info = $images[$file]['imageinfo'][0] ?? [];
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

    private function pages(string $endpoint, array $titles, array $params): array
    {
        $pages = [];
        $missing = [];
        foreach ($titles as $title) {
            $cached = Cache::get('timeline.wikimedia.page.v1.'.sha1($endpoint.$title));
            if ($cached !== null) $pages[$title] = $cached;
            else $missing[] = $title;
        }
        if ($missing) {
            $data = $this->get($endpoint, array_merge($params, ['titles' => implode('|', $missing)]));
            if (!isset($data['query']['pages']) || !is_array($data['query']['pages'])) {
                Log::warning('Invalid timeline Wikimedia pages response', ['endpoint' => $endpoint, 'http_status' => 200]);
                throw new \RuntimeException('Invalid Wikimedia pages response.');
            }
            $found = collect($data['query']['pages'])->keyBy('title');
            $aliases = collect(array_merge($data['query']['normalized'] ?? [], $data['query']['redirects'] ?? []))->pluck('to', 'from');
            foreach ($missing as $original) {
                $title = $original;
                for ($i = 0; $i < 3 && isset($aliases[$title]); $i++) $title = $aliases[$title];
                $pages[$original] = $found->get($title, []);
                Cache::put('timeline.wikimedia.page.v1.'.sha1($endpoint.$original), $pages[$original], now()->addMinutes(config('wikimedia.cache_minutes')));
            }
        }
        return $pages;
    }

    private function get($endpoint, array $params): array
    {
        if (!$this->deadline) $this->deadline = microtime(true) + config('wikimedia.discovery_budget');
        return $this->client->get($endpoint, $params, $endpoint === self::QUERY_ENDPOINT ? $this->deadline - 8 : $this->deadline);
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

    public function query(int $year, int $range, string $property = 'P571', int $batch = 0): string
    {
        if (!in_array($property, self::PROPERTIES, true) || $batch < 0 || $batch >= config('wikimedia.max_batches')) {
            throw new \InvalidArgumentException('Invalid discovery batch.');
        }
        $start = sprintf('%04d-01-01T00:00:00Z', max(1, $year - $range));
        $end = sprintf('%04d-12-31T23:59:59Z', min(9999, $year + $range));
        $limit = (int) config('wikimedia.candidate_limit');
        $offset = $batch * $limit;
        // Indexed date lookup with one notability join. Metadata comes from wbgetentities.
        return <<<SPARQL
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wikibase: <http://wikiba.se/ontology#>
PREFIX hint: <http://www.bigdata.com/queryHints#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
SELECT ?item ?date ?sitelinks WHERE {
  hint:Query hint:optimizer "None" .
  ?item wdt:$property ?date . hint:Prior hint:rangeSafe true .
  FILTER(?date >= "$start"^^xsd:dateTime && ?date <= "$end"^^xsd:dateTime)
  ?item wikibase:sitelinks ?sitelinks . FILTER(?sitelinks >= 15)
} ORDER BY DESC(?sitelinks) ASC(?date) ASC(?item) LIMIT $limit OFFSET $offset
SPARQL;
    }
}
