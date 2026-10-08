<?php

namespace Tests\Review;

use App\Tag;
use Illuminate\Support\Facades\DB;

class DiscoverLevelsTest extends ReviewTestCase
{
    public function rows()
    {
        return collect([
            ['key' => 'suzuki', 'title' => 'Equivalent to the Suzuki series', 'numbers' => [7, 1, 3, 2, 6, 5, 4]],
            ['key' => 'rcm', 'title' => 'Equivalent to the RCM levels', 'numbers' => [10, 1, 2, 3, 4, 5, 6, 7, 8, 9]],
            ['key' => 'abrsm', 'title' => 'Equivalent to the ABRSM levels', 'numbers' => [8, 1, 2, 3, 4, 5, 6, 7]],
        ])->map(function ($system) {
            return ['row' => 'gallery', 'type' => 'collection', 'title' => $system['title'], 'content' => collect($system['numbers'])->map(function ($number) use ($system) {
                return (new Tag)->forceFill(['name' => ucfirst($system['key']).' '.($system['key'] === 'suzuki' ? 'Book ' : '').$number, 'pieces_count' => $number === 1 ? 1 : $number * 5]);
            })];
        });
    }

    public function test_tabs_keep_their_own_numeric_order_counts_labels_links_and_palette()
    {
        $rows = $this->rows();
        $original = $rows->map(function ($row) { return $row['content']->map->getAttributes()->all(); })->all();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $html = view('webapp.discover.rows.levels', compact('rows'))->render();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(3, $xpath->query('//button[@role="tab"]')->length);
        $this->assertSame(['RCM', 'ABRSM', 'Suzuki'], array_map(function ($tab) { return trim($tab->textContent); }, iterator_to_array($xpath->query('//button[@role="tab"]'))));
        $this->assertSame('true', $xpath->query('//button[@id="discover-levels-rcm-tab"]')->item(0)->getAttribute('aria-selected'));
        foreach (['suzuki' => 7, 'rcm' => 10, 'abrsm' => 8] as $key => $count) {
            $cards = $xpath->query('//div[@id="discover-levels-'.$key.'"]//a');
            $this->assertSame($count, $cards->length);
            foreach ($cards as $index => $card) {
                $number = $index + 1;
                $unit = $key === 'suzuki' ? 'Book' : 'Level';
                $this->assertStringContainsString($unit.' '.$number, $card->textContent);
                $this->assertStringContainsString($number === 1 ? '1 piece' : ($number * 5).' pieces', $card->textContent);
                $sourceName = ucfirst($key).' '.($key === 'suzuki' ? 'Book ' : '').$number;
                $this->assertSame(route('webapp.search.results', ['search' => $sourceName]), $card->getAttribute('href'));
            }
            $this->assertStringContainsString(gradient('darkblue')[0], $cards->item(0)->getAttribute('style'));
            $this->assertStringContainsString(gradient('red')[0], $cards->item($count - 1)->getAttribute('style'));
            $this->assertSame($count, count(array_unique(array_map(function ($card) { return $card->getAttribute('href'); }, iterator_to_array($cards)))));
            $this->assertSame($count, count(array_unique(array_map(function ($card) { return $card->getAttribute('style'); }, iterator_to_array($cards)))));
        }
        $this->assertSame($original, $rows->map(function ($row) { return $row['content']->map->getAttributes()->all(); })->all());
    }

    public function test_empty_systems_are_disabled_and_the_first_available_system_opens()
    {
        $rows = $this->rows();
        $rows[1]['content']->splice(0);
        $html = view('webapp.discover.rows.levels', compact('rows'))->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//button[@id="discover-levels-rcm-tab" and @disabled]')->length);
        $this->assertSame('true', $xpath->query('//button[@id="discover-levels-abrsm-tab"]')->item(0)->getAttribute('aria-selected'));
        $this->assertSame('tab-pane active', $xpath->query('//div[@id="discover-levels-abrsm"]')->item(0)->getAttribute('class'));
        $rows = collect();
        $this->assertStringNotContainsString('discover-levels-heading', view('webapp.discover.rows.levels', compact('rows'))->render());
    }
}
