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
        $this->assertCount(1, $xpath->query('.//nav[@aria-label="Breadcrumb"]', $headers[0]));
        $this->assertCount(1, $xpath->query('.//nav[@aria-label="Breadcrumb"]//*[@aria-current="page" and not(self::a)]', $headers[0]));
        $this->assertCount(0, $xpath->query('.//nav[@aria-label="Breadcrumb"]//a[contains(@class,"btn")]', $headers[0]));

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
            ['admin.design-system.index', 'Design System'],
            ['admin.access-limits.index', 'Access Limits'],
            ['admin.ebooks.index', 'eBooks'],
            ['admin.ebooks.create', 'New eBook'],
            ['admin.ebooks.topics.index', 'eBook Topics'],
            ['admin.escores.index', 'eScores'],
            ['admin.pieces.create', 'New piece'],
            ['admin.users.index', 'Users'],
            ['admin.editors.index', 'Editors'],
            ['admin.notifications.index', 'Notifications'],
            ['admin.analytics.users', 'User analytics'],
            ['admin.analytics.favorites', 'Favorite folders'],
            ['admin.analytics.memberships', 'Membership analytics'],
            ['admin.analytics.pieces', 'Repertoire analytics'],
            ['admin.analytics.composers', 'Composer analytics'],
        ];
    }

    public function test_header_actions_keep_their_links_modals_and_form_semantics()
    {
        $this->actingAs(create(Admin::class), 'admin');
        [$xpath, $header] = $this->header($this->get(route('admin.ebooks.index'))->assertOk()->getContent());
        $this->assertSame(route('admin.ebooks.create'), $xpath->query('.//a[contains(@class, "btn-dark")]', $header)[0]->getAttribute('href'));
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
        $parent = $xpath->query('.//nav[@aria-label="Breadcrumb"]//a[normalize-space(.)="Pieces"]', $header);
        $this->assertSame(route('admin.pieces.index'), $parent[0]->getAttribute('href'));
        $this->assertCount(0, $xpath->query('.//a[contains(@class,"btn-secondary")]', $header));
        $this->assertSame(1, $xpath->query('//form[@id="edit-form"]//input[@name="_method" and @value="PATCH"]')->length);

        [$xpath, $header] = $this->header($this->get(route('admin.editors.index'))->assertOk()->getContent());
        $forms = $xpath->query('//*[@id="add-modal"]//form[@method="POST"]');
        $this->assertSame(1, $forms->length);
        $this->assertSame(route('admin.editors.store'), $forms[0]->getAttribute('action'));
        $this->assertSame(1, $xpath->query('.//button[@type="submit"]', $forms[0])->length);
    }

    public function test_email_report_view_uses_the_same_header_with_fixture_data()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.home'))->assertOk(); // Share the normal session/error view context.
        $event = (object) ['emails_count' => 1, 'delivered_count' => 1, 'failed_count' => 0, 'opens_count' => 0, 'clicks_count' => 0, 'list_id' => 'free-pick.7', 'name' => 'Free Pick', 'sent_at' => now()];
        $this->header(view('admin.pages.reports.show.index', ['event' => $event, 'recipients' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12), 'filters' => ['search' => '', 'sort' => 'date_desc', 'status' => 'all', 'per_page' => 12]])->render());
    }

    public function test_breadcrumbs_escape_labels_keep_explicit_destinations_and_do_not_link_the_current_page()
    {
        $html = view('admin.components.breadcrumb', ['items' => [
            ['label' => 'Email lists', 'url' => route('admin.subscriptions.lists.index')],
            ['label' => '<unsafe> & Free Pick', 'url' => 'https://unused.example'],
        ]])->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $this->assertCount(0, $xpath->query('//unsafe'));
        $this->assertCount(1, $xpath->query('//nav//a'));
        $this->assertSame(route('admin.subscriptions.lists.index'), $xpath->query('//nav//a')[0]->getAttribute('href'));
        $this->assertSame('<unsafe> & Free Pick', $xpath->query('//*[@aria-current="page"]')[0]->textContent);
        $this->assertCount(1, $xpath->query('//span[@aria-hidden="true"]'));
    }

    public function test_nested_lesson_breadcrumbs_keep_the_course_and_directory_destinations()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $course = Model::withoutEvents(function () {
            return create(\App\CrashCourse\CrashCourse::class, ['title' => 'Rhythm <basics>']);
        });
        [$xpath, $header] = $this->header($this->get(route('admin.crashcourses.lessons.create', $course))->assertOk()->getContent());
        $links = $xpath->query('.//nav[@aria-label="Breadcrumb"]//a', $header);
        $this->assertSame([
            route('admin.home'), route('admin.crashcourses.index'), route('admin.crashcourses.edit', $course),
        ], array_map(function ($link) { return $link->getAttribute('href'); }, iterator_to_array($links)));
        $this->assertSame('Rhythm <basics>', $links[2]->textContent);
        $this->assertSame('New lesson', $xpath->query('.//*[@aria-current="page"]', $header)[0]->textContent);
        $this->assertCount(0, $xpath->query('.//basics | .//a[contains(@class,"btn-secondary")]', $header));
    }
}
