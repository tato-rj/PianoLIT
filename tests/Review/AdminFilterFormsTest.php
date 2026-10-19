<?php

namespace Tests\Review;

use App\{Admin, User};
use Illuminate\Database\Eloquent\Model;

class AdminFilterFormsTest extends ReviewTestCase
{
    protected function xpath($html)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }

    public function test_visit_sort_retains_multiple_platforms_and_independent_filters_without_a_conflicting_order_or_page()
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs(create(Admin::class), 'admin');
        foreach ([route('admin.analytics.users'), route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits'])] as $url) {
            $response = $this->get($url.(strpos($url, '?') === false ? '?' : '&').http_build_query([
                'platform' => ['webapp', 'app'], 'range' => 'custom', 'from' => '2026-10-01', 'to' => '2026-10-19',
                'visit_sort' => 'duration', 'visit_order' => 'asc', 'visits_page' => 2, 'history_page' => 3,
            ]))->assertOk();
            $xpath = $this->xpath($response->getContent());
            $form = $xpath->query('//form[@aria-label="Visit sorting"]')[0];
            $pairs = [];
            foreach ($xpath->query('.//input[@type="hidden"]', $form) as $input) {
                $pairs[] = urlencode($input->getAttribute('name')).'='.urlencode($input->getAttribute('value'));
            }
            parse_str(implode('&', $pairs), $submitted);
            $this->assertSame(['webapp', 'app'], $submitted['platform']);
            $this->assertSame('custom', $submitted['range']);
            $this->assertSame('2026-10-01', $submitted['from']);
            $this->assertSame('2026-10-19', $submitted['to']);
            $this->assertSame('3', $submitted['history_page']);
            $this->assertArrayNotHasKey('visits_page', $submitted);
            $this->assertArrayNotHasKey('visit_order', $submitted);
            $this->assertSame('duration', $xpath->query('.//select[@name="visit_sort"]/option[@selected]', $form)[0]->getAttribute('value'));
            $buttons = $xpath->query('.//button[@name="visit_order"]', $form);
            $this->assertCount(2, $buttons);
            $this->assertSame('asc', $buttons[0]->getAttribute('value'));
            $this->assertSame('desc', $buttons[1]->getAttribute('value'));
            if (isset($submitted['tab'])) $this->assertSame('visits', $submitted['tab']);
        }
    }

    public function test_recursive_hidden_fields_escape_values_and_safe_previews_cannot_submit()
    {
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        $html = view('admin.components.filters.form', [
            'id' => 'test-filters', 'label' => 'Test filters',
            'hidden' => ['platform' => ['webapp', 'app'], 'meta' => ['text' => '"><script>bad</script>']],
            'fields' => [['type' => 'search', 'name' => 'search', 'label' => 'Search'], ['name' => 'sort', 'label' => 'Sort by', 'options' => ['date' => 'Date']]],
            'state' => ['search' => '<img src=x>'],
        ])->render();
        $this->assertStringNotContainsString('<script>bad</script>', $html);
        $this->assertStringNotContainsString('<img src=x>', $html);
        $xpath = $this->xpath($html);
        $this->assertCount(1, $xpath->query('//input[@name="platform[0]" and @value="webapp"]'));
        $this->assertCount(1, $xpath->query('//input[@name="platform[1]" and @value="app"]'));
        $this->assertSame('"><script>bad</script>', $xpath->query('//input[@name="meta[text]"]')[0]->getAttribute('value'));
        $this->assertCount(0, $xpath->query('//input[@required]|//select[@required]'));
        foreach ($xpath->query('//label') as $label) $this->assertCount(1, $xpath->query('//*[@id="'.$label->getAttribute('for').'"]'));
        $preview = view('admin.components.filters.form', ['id' => 'preview-filters', 'label' => 'Sample', 'preview' => true])->render();
        $previewXpath = $this->xpath($preview);
        $this->assertCount(0, $previewXpath->query('//form|//button[@type="submit"]'));
    }
}
