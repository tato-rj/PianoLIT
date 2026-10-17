<?php

namespace Tests\Review;

use App\{Admin, User};
use Illuminate\Support\Facades\DB;

class AdminAccessLimitsTest extends ReviewTestCase
{
    public function test_access_limits_require_an_admin_session_and_use_the_admin_host()
    {
        $url = route('admin.access-limits.index');
        $this->assertSame('admin.localhost', parse_url($url, PHP_URL_HOST));
        $this->assertSame('/access-limits', parse_url($url, PHP_URL_PATH));
        $this->withExceptionHandling()->get($url)->assertRedirect(route('admin.login.show'));
        $this->actingAs(create(User::class), 'web')->get($url)->assertRedirect(route('admin.login.show'));
        $this->getJson($url)->assertUnauthorized();
    }

    public function test_settings_page_shows_only_the_access_matrix_without_mutations()
    {
        $this->actingAs(create(Admin::class), 'admin');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->get(route('admin.access-limits.index'))->assertOk()
            ->assertViewIs('admin.pages.settings.access-limits')
            ->assertSee('Saved markings stay saved')->assertSee('Active trials')
            ->assertSee('public-domain PDFs')->assertDontSee('data-admin-datatable', false)
            ->assertDontSee('Signup &amp; Premium prompts', false)->assertDontSee('access-prompts', false);
        $xpath = $this->xpath($response);
        $this->assertSame(1, $xpath->query('//ul[@id="settings" and contains(@class,"show")]')->length);
        $this->assertSame(1, $xpath->query('//ul[@id="settings"]//a[@href="'.route('admin.access-limits.index').'" and @aria-current="page"]')->length);
        $this->assertSame(3, $xpath->query('//main//*[contains(concat(" ",@class," ")," admin-table ")]')->length);
        foreach (['free-pick' => 6, 'regular-pieces' => 6, 'account-features' => 6] as $id => $count) {
            $this->assertSame($count, $xpath->query('//table[@id="'.$id.'"]/tbody/tr')->length);
            $this->assertSame(1, $xpath->query('//a[@href="#'.$id.'-title"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'-title"]')->length);
        }
        foreach (['free-pick', 'regular-pieces', 'account-features'] as $id) {
            $this->assertSame(['Feature', 'Guest', 'Registered user', 'Paying member'], array_map(function ($node) { return trim($node->textContent); }, iterator_to_array($xpath->query('//table[@id="'.$id.'"]/thead/tr/th[@scope="col"]'))));
        }
        $this->assertCell($xpath, 'free-pick', 'Performance video', 2, '10-second preview');
        $this->assertCell($xpath, 'free-pick', 'Performance video', 3, 'Available');
        $this->assertCell($xpath, 'free-pick', 'Audio', 2, '10-second preview');
        $this->assertCell($xpath, 'free-pick', 'Audio', 3, 'Available');
        $this->assertCell($xpath, 'free-pick', 'Synthesia', 2, '10-second preview');
        $this->assertCell($xpath, 'free-pick', 'Read the score', 2, 'Blocked');
        $this->assertCell($xpath, 'free-pick', 'Read the score', 3, 'Available');
        $this->assertCell($xpath, 'free-pick', 'Read, add and save annotations', 2, 'Account required');
        $this->assertCell($xpath, 'free-pick', 'Read, add and save annotations', 3, 'Available');
        foreach (['Performance video', 'Synthesia'] as $feature) {
            $this->assertCell($xpath, 'regular-pieces', $feature, 2, 'Blocked');
            $this->assertCell($xpath, 'regular-pieces', $feature, 3, '10-second preview');
            $this->assertCell($xpath, 'regular-pieces', $feature, 4, 'Available');
        }
        $this->assertCell($xpath, 'regular-pieces', 'Audio', 2, 'Blocked');
        $this->assertCell($xpath, 'regular-pieces', 'Audio', 3, '10-second preview');
        foreach (['free-pick', 'regular-pieces'] as $table) {
            $this->assertCell($xpath, $table, 'Download & print', 2, 'Blocked');
            $this->assertCell($xpath, $table, 'Download & print', 3, 'Blocked');
            $this->assertCell($xpath, $table, 'Download & print', 4, 'Available');
        }
        $this->assertCell($xpath, 'account-features', 'Create eScores', 2, 'Account required');
        $this->assertCell($xpath, 'account-features', 'Create eScores', 3, 'Blocked');
        $this->assertCell($xpath, 'account-features', 'Create eScores', 4, 'Available');
        $this->assertSame(1, $xpath->query('//form[@data-access-limits-form]')->length);
        $this->assertSame(54, $xpath->query('//select[@data-status-select]')->length);
        $this->assertSame(6, $xpath->query('//select[@data-status-select and @disabled]')->length);
        $response->assertDontSee('Browse repertoire and open piece pages')->assertSee('Save changes');
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
        }
        if ($path = getenv('ADMIN_ACCESS_LIMITS_PREVIEW_FILE')) file_put_contents($path, $response->getContent());
    }

    public function test_select_options_and_atomic_save_drive_reloaded_values()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.access-limits.index'))->assertOk();
        $xpath = $this->xpath($response);
        foreach ($xpath->query('//select[@data-status-select]') as $select) {
            $name = $select->getAttribute('name');
            $limited = preg_match('/\[(performance|synthesia|audio|search|collections|folders|folder_pieces)\]/', $name);
            $this->assertSame($limited ? 3 : 2, $xpath->query('option', $select)->length);
        }
        $rules = \App\Services\WebApp\AccessPolicy::defaults();
        $rules['regular']['performance']['guest'] = 'available';
        $rules['account']['folders']['registered'] = 'limited';
        $rules['account']['folder_pieces']['registered'] = 'limited';
        $this->put(route('admin.access-limits.update'), ['revision' => 0, 'rules' => $rules])->assertRedirect(route('admin.access-limits.index'));
        $this->assertDatabaseHas('web_access_policies', ['id' => 1, 'revision' => 1, 'updated_by' => auth('admin')->id()]);
        $saved = json_decode(DB::table('web_access_policies')->value('rules'), true);
        $this->assertSame($rules, $saved);
        $html = $this->get(route('admin.access-limits.index'))->assertOk();
        if ($path = getenv('ADMIN_ACCESS_LIMITS_SAVED_FILE')) file_put_contents($path, $html->getContent());
        $xpath = $this->xpath($html);
        $this->assertCell($xpath, 'account-features', 'Favorite folders', 3, 'Up to 2 folders');
        $this->assertCell($xpath, 'account-features', 'Pieces per favorite folder', 3, 'Up to 5 pieces per folder');
        $this->assertSame('available', $xpath->query('//select[@name="rules[regular][performance][guest]"]/option[@selected]')->item(0)->getAttribute('value'));
    }

    public function test_invalid_and_unknown_rules_or_guest_account_access_do_not_save()
    {
        $this->actingAs(create(Admin::class), 'admin')->withExceptionHandling();
        foreach (['limited_score', 'guest_account', 'unknown_feature', 'missing_role'] as $case) {
            $rules = \App\Services\WebApp\AccessPolicy::defaults();
            if ($case === 'limited_score') $rules['regular']['score']['guest'] = 'limited';
            if ($case === 'guest_account') $rules['account']['folders']['guest'] = 'available';
            if ($case === 'unknown_feature') $rules['regular']['injected'] = ['guest' => 'available'];
            if ($case === 'missing_role') unset($rules['regular']['audio']['member']);
            $this->putJson(route('admin.access-limits.update'), ['revision' => 0, 'rules' => $rules])->assertStatus(422);
            $this->assertDatabaseCount('web_access_policies', 0);
        }
    }

    public function test_stale_admin_cannot_overwrite_a_newer_policy()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $rules = \App\Services\WebApp\AccessPolicy::defaults();
        $this->put(route('admin.access-limits.update'), ['revision' => 0, 'rules' => $rules])->assertRedirect();
        $stale = $rules;
        $stale['regular']['audio']['guest'] = 'available';
        $this->put(route('admin.access-limits.update'), ['revision' => 0, 'rules' => $stale])->assertRedirect()->assertSessionHas('error');
        $this->assertSame($rules, json_decode(DB::table('web_access_policies')->value('rules'), true));
        $this->assertDatabaseHas('web_access_policies', ['id' => 1, 'revision' => 1]);
    }

    private function xpath($response)
    {
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        return new \DOMXPath($document);
    }

    private function assertCell($xpath, $table, $feature, $column, $text)
    {
        $cell = $xpath->query('//table[@id="'.$table.'"]/tbody/tr[td[1]/span[text()="'.$feature.'"]]/td['.$column.']');
        $this->assertSame(1, $cell->length);
        $this->assertStringContainsString($text, $cell->item(0)->textContent);
    }
}
