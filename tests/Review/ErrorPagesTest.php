<?php

namespace Tests\Review;

use App\Exceptions\Handler;
use App\Support\ErrorPage;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorPagesTest extends ReviewTestCase
{
    public function test_all_browser_hubs_share_the_page_with_the_matching_brand_and_real_status()
    {
        config(['app.debug' => false, 'app.short_url' => 'pianolit.test']);
        foreach (['pianolit.test', 'admin.pianolit.test', 'my.pianolit.test', 'learn.pianolit.test', 'shop.pianolit.test', 'blog.pianolit.test', 'games.pianolit.test', 'metronome.pianolit.test'] as $host) {
            foreach ([400, 401, 402, 403, 404, 405, 408, 410, 413, 418, 419, 422, 429, 500, 502, 503, 504] as $status) {
                $request = Request::create('https://'.$host.'/missing', 'GET');
                $this->app->instance('request', $request);
                $response = $this->app->make(Handler::class)->render($request, new HttpException($status, 'PRIVATE EXCEPTION DETAILS', null, ['Retry-After' => '60']));
                $this->assertSame($status, $response->getStatusCode());
                $this->assertSame('60', $response->headers->get('Retry-After'));
                $html = $response->getContent();
                $this->assertStringContainsString('data-brand="'.($host === 'admin.pianolit.test' ? 'admin' : 'regular').'"', $html);
                $this->assertStringContainsString('<span>'.$status.'</span>', $html);
                $this->assertStringNotContainsString('PRIVATE EXCEPTION DETAILS', $html);
                $this->assertStringNotContainsString('<script', $html);
                $this->assertStringNotContainsString('rel="stylesheet"', $html);
            }
        }
    }

    public function test_browser_errors_render_without_database_session_or_asset_manifest_services()
    {
        $database = $this->app->make('db');
        $this->app->instance('db', \Mockery::mock());
        $this->app->instance('session', \Mockery::mock());
        $this->app->instance(\Illuminate\Foundation\Mix::class, \Mockery::mock());
        $this->app->instance('request', Request::create('https://admin.pianolit.test/upload', 'POST'));
        try {
            $html = view('errors.413')->render();
        } finally {
            $this->app->instance('db', $database);
        }
        $this->assertStringContainsString('A little too much to carry', $html);
        $this->assertStringContainsString('Try a smaller file', $html);
    }

    public function test_retry_links_do_not_replay_form_posts_or_leave_the_current_origin()
    {
        config(['app.short_url' => 'pianolit.test']);
        $request = Request::create('https://admin.pianolit.test/upload', 'POST');
        foreach (['https://outside.test/editor', '//outside.test/editor', 'https://admin.pianolit.test/upload', 'https://admin.pianolit.test:444/editor', 'https://admin.pianolit.test//outside.test/editor', 'https://user@admin.pianolit.test/editor', 'http://admin.pianolit.test/editor', "https://admin.pianolit.test/\\outside.test"] as $referer) {
            $request->headers->set('referer', $referer);
            foreach ([413, 500] as $status) {
                $this->assertSame('/', ErrorPage::context($request, $status)['primary']['url']);
            }
        }
        $request->headers->set('referer', 'https://admin.pianolit.test/editor?page=2#upload');
        foreach ([413, 419, 500] as $status) {
            $this->assertSame('/editor?page=2', ErrorPage::context($request, $status)['primary']['url']);
        }
        $get = Request::create('https://shop.pianolit.test/products?category=ebooks', 'GET');
        $this->assertSame('/products?category=ebooks', ErrorPage::context($get, 500)['primary']['url']);
        $this->assertSame('/login', ErrorPage::context($get, 401)['primary']['url']);
        $get = Request::create('https://shop.pianolit.test//outside.test/missing');
        $this->assertSame('/', ErrorPage::context($get, 500)['primary']['url']);
    }

    public function test_json_error_contracts_remain_json()
    {
        config(['app.debug' => false]);
        foreach (['/api/v2/missing', '/api/missing', '/missing'] as $path) {
            $request = Request::create('https://pianolit.test'.$path, 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
            $this->app->instance('request', $request);
            $response = $this->app->make(Handler::class)->render($request, new HttpException(413));
            $this->assertSame(413, $response->getStatusCode());
            $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
            $this->assertStringNotContainsString('error-page', $response->getContent());
        }
    }

    public function test_rotated_session_keeps_its_explicit_recovery_link()
    {
        $this->app->instance('request', Request::create('https://learn.pianolit.test/course', 'GET'));
        $html = view('errors.shared-session-expired')->render();
        $this->assertStringContainsString('Your session changed in another tab', $html);
        $this->assertStringContainsString('href="/login?restart_session=1"', $html);
    }

    public function test_error_pages_have_their_own_admin_page_with_the_real_previews()
    {
        config(['app.short_url' => 'pianolit.test']);
        $this->withExceptionHandling()->get(route('admin.error-pages.index'))->assertRedirect(route('admin.login.show'));
        $this->actingAs(create(\App\Admin::class), 'admin');
        $response = $this->get(route('admin.error-pages.index'))->assertOk();
        $response->assertViewIs('admin.pages.settings.error-pages');
        $html = $response->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $frames = $xpath->query('//iframe[@sandbox and @inert]');
        $this->assertSame(3, $frames->length);
        $this->assertSame(1, $xpath->query('//ul[@id="settings" and contains(@class, "show")]')->length);
        $this->assertSame(1, $xpath->query('//a[@href="'.route('admin.error-pages.index').'" and @aria-current="page"]')->length);
        $this->assertSame(0, $xpath->query('//a[@href="'.route('admin.design-system.index').'" and @aria-current="page"]')->length);
        foreach ($frames as $index => $frame) {
            $page = $frame->getAttribute('srcdoc');
            $this->assertStringContainsString('data-brand="'.($index === 1 ? 'admin' : 'regular').'"', $page);
            $this->assertStringContainsString('error-page__illustration', $page);
        }
        $designSystem = $this->get(route('admin.design-system.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('id="error-page-previews"', $designSystem);
        $this->assertStringNotContainsString('id="ds-error-pages"', $designSystem);
        $this->assertStringNotContainsString('error page preview', $designSystem);
    }
}
