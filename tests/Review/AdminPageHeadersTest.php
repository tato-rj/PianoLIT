<?php

namespace Tests\Review;

use App\{Admin, Piece};
use Illuminate\Database\Eloquent\Model;

class AdminPageHeadersTest extends ReviewTestCase
{
    protected function header($html)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $headers = $xpath->query('//main[@id="admin-main"]//header[@id="page-title"]');
        $this->assertSame(1, $headers->length);
        $this->assertSame(1, $xpath->query('.//h1', $headers[0])->length);
        $this->assertSame(0, $xpath->query('.//h1//svg', $headers[0])->length);
        $this->assertSame(1, $xpath->query('.//button[@id="admin-account-toggle"]', $headers[0])->length);
        $this->assertSame(1, $xpath->query('.//a[@data-bs-target="#notifications-panel"]', $headers[0])->length);
        $this->assertSame(1, $xpath->query('//*[@id="admin-account-toggle"]')->length);
        $this->assertSame(0, $xpath->query('//*[@class="title-text"]')->length);

        return [$xpath, $headers[0]];
    }

    /** @dataProvider pages */
    public function test_admin_pages_share_one_heading_with_account_controls($route, $title)
    {
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route($route))->assertOk();
        [$xpath, $header] = $this->header($response->getContent());
        $this->assertSame($title.'.', $xpath->query('.//h1', $header)[0]->textContent);
    }

    public static function pages()
    {
        return [
            ['admin.home', 'Overview'],
            ['admin.ebooks.index', 'eBooks'],
            ['admin.ebooks.create', 'New eBook'],
            ['admin.ebooks.topics.index', 'eBook Topics'],
            ['admin.escores.index', 'eScores'],
            ['admin.pieces.create', 'New piece'],
            ['admin.users.index', 'Users'],
            ['admin.users.logs', 'Activity Logs'],
            ['admin.activity.index', 'Visit timelines'],
            ['admin.editors.index', 'Editors'],
            ['admin.notifications.index', 'Notifications'],
            ['admin.analytics.users', 'User analytics'],
            ['admin.analytics.logs', 'Activity analytics'],
            ['admin.analytics.favorites', 'Favorite folders'],
            ['admin.analytics.memberships', 'Membership analytics'],
            ['admin.analytics.pieces', 'Repertoire analytics'],
            ['admin.analytics.subscriptions', 'Subscriber analytics'],
            ['admin.analytics.blog', 'Blog analytics'],
        ];
    }

    public function test_header_actions_keep_their_links_modals_and_form_semantics()
    {
        $this->actingAs(create(Admin::class), 'admin');
        [$xpath, $header] = $this->header($this->get(route('admin.ebooks.index'))->assertOk()->getContent());
        $this->assertSame(route('admin.ebooks.create'), $xpath->query('.//a[contains(@class, "btn-primary")]', $header)[0]->getAttribute('href'));
        [$xpath, $header] = $this->header($this->get(route('admin.ebooks.topics.index'))->assertOk()->getContent());
        $this->assertSame(1, $xpath->query('.//button[@type="button" and @data-bs-target="#add-modal"]', $header)->length);
        $this->assertSame(1, $xpath->query('//*[@id="add-modal"]')->length);
        [$xpath, $header] = $this->header($this->get(route('admin.users.index'))->assertOk()->getContent());
        $this->assertSame(0, $xpath->query('.//form[@action="'.route('admin.memberships.validate.all').'"]', $header)->length);
        $this->assertSame(0, $xpath->query('//*[contains(text(), "Validate all subscriptions")]')->length);

        $piece = Model::withoutEvents(function () { return create(Piece::class, ['name' => 'A very long repertoire title with <unsafe> text']); });
        $response = $this->get(route('admin.pieces.edit', $piece))->assertOk();
        [$xpath, $header] = $this->header($response->getContent());
        $this->assertSame('Edit '.$piece->name.'.', $xpath->query('.//h1', $header)[0]->textContent);
        $this->assertSame(0, $xpath->query('.//unsafe', $header)->length);
        $back = $xpath->query('.//a[contains(@class, "btn-secondary")]', $header);
        $this->assertSame(route('admin.pieces.index'), $back[0]->getAttribute('href'));
        $this->assertSame(1, $xpath->query('//form[@id="edit-form"]//input[@name="_method" and @value="PATCH"]')->length);

        [$xpath, $header] = $this->header($this->get(route('admin.editors.index'))->assertOk()->getContent());
        $forms = $xpath->query('//*[@id="add-modal"]//form[@method="POST"]');
        $this->assertSame(1, $forms->length);
        $this->assertSame(route('admin.editors.store'), $forms[0]->getAttribute('action'));
        $this->assertSame(1, $xpath->query('.//button[@type="submit"]', $forms[0])->length);
    }

    public function test_mysql_backed_report_views_use_the_same_header_with_fixture_data()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.home'))->assertOk(); // Share the normal session/error view context.
        $cases = [
            'composers' => ['composersAnalytics' => collect(), 'composersCount' => 0, 'composersWithFewPieces' => [],
                'upcomingBirthdays' => collect(), 'upcomingDeathdays' => collect(), 'periodsAnalytics' => collect(),
                'countriesAnalytics' => collect(), 'composers' => collect(), 'countriesArray' => [['Country', 'Composers']]],
            'quizzes' => ['levelAnalytics' => collect(), 'topicAnalytics' => collect(), 'topicsCount' => 0,
                'quizzes' => collect(), 'results_graph' => collect()],
        ];
        foreach ($cases as $name => $data) {
            $this->header(view('admin.pages.analytics.'.$name.'.index', $data)->render());
        }
        $event = (object) ['emails_count' => 1, 'delivered_count' => 1, 'opens_count' => 0, 'clicks_count' => 0, 'list_id' => 7];
        $this->header(view('admin.pages.reports.show.index', ['event' => $event])->render());
    }
}
