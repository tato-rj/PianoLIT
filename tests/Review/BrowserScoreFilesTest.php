<?php

namespace Tests\Review;

use App\{Admin, Piece, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Storage, URL};

class BrowserScoreFilesTest extends ReviewTestCase
{
    private const PATH = 'app/score/example.pdf';

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Storage::fake('public');
    }

    public function test_the_copied_admin_link_requires_the_admin_session_even_for_head_and_range_requests()
    {
        Storage::disk('public')->put(self::PATH, '%PDF-test bytes');
        $piece = Model::withoutEvents(fn () => create(Piece::class, ['score_path' => self::PATH]));
        $preview = route('admin.pieces.score-preview', $piece);
        $this->get($preview)->assertRedirect(route('admin.login.show'));
        $url = route('admin.pieces.score-file', 'example.pdf');
        $this->get($url)->assertRedirect(route('admin.login.show'));
        $this->call('HEAD', $url)->assertRedirect(route('admin.login.show'));
        $this->actingAs(Model::withoutEvents(fn () => create(User::class)), 'web');
        $this->get($url, ['Range' => 'bytes=0-3'])->assertRedirect(route('admin.login.show'));

        $this->actingAs(create(Admin::class), 'admin');
        $this->get($preview)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $response = $this->get($url, ['Range' => 'bytes=0-3'])->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 0-3/15')->assertHeader('Content-Type', 'application/pdf');
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->call('HEAD', $url)->assertOk()->assertHeader('Content-Length', '15');

        $this->get(route('admin.pieces.score-file', 'missing.pdf'))->assertNotFound();
        Storage::disk('public')->put('other/private.pdf', 'Other file');
        symlink(Storage::disk('public')->path('other/private.pdf'), Storage::disk('public')->path('app/score/link.pdf'));
        $this->get(route('admin.pieces.score-file', 'link.pdf'))->assertNotFound();
    }

    public function test_web_read_and_export_check_access_on_each_request_and_support_legacy_saved_paths()
    {
        $piece = Model::withoutEvents(fn () => create(Piece::class, ['score_path' => 'public/'.self::PATH, 'score_url' => null]));
        Storage::disk('public')->put(self::PATH, '%PDF-test bytes');
        $read = route('webapp.pieces.score.read', $piece);
        $download = route('webapp.pieces.score', $piece);
        $this->get($read, ['Range' => 'bytes=0-3'])->assertForbidden();
        $this->call('HEAD', $download)->assertForbidden();
        $this->actingAs(Model::withoutEvents(fn () => create(User::class)), 'web');
        $this->get($read)->assertForbidden();

        $this->actingAs(Model::withoutEvents(fn () => create(User::class, ['super_user' => true])), 'web');
        $response = $this->get($read)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('%PDF-test bytes', file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->get($download)->assertOk()->assertDownload('example.pdf');
        $piece->updateQuietly(['score_url' => 'https://publisher.example/edition']);
        $this->get($read)->assertNotFound();
        $this->get($download)->assertNotFound();
    }

    public function test_previews_deliver_only_a_small_first_page_or_a_signed_center_excerpt()
    {
        $piece = Model::withoutEvents(fn () => create(Piece::class, ['score_path' => self::PATH, 'score_url' => null]));
        // Three distinct colored bands show exactly which area reaches the browser.
        $pdf = new \FPDF('P', 'pt', [400, 600]);
        $pdf->AddPage();
        $pdf->SetFillColor(255, 0, 0);
        $pdf->Rect(0, 0, 400, 600, 'F');
        $pdf->SetFillColor(0, 255, 0);
        $pdf->Rect(0, 200, 400, 200, 'F');
        $pdf->AddPage();
        $pdf->SetFillColor(0, 0, 255);
        $pdf->Rect(0, 0, 400, 600, 'F');
        Storage::disk('public')->put(self::PATH, $pdf->Output('S'));

        $preview = route('webapp.pieces.score.preview', $piece);
        $bytes = $this->get($preview)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->getContent();
        [$width, $height] = getimagesizefromstring($bytes);
        $this->assertSame(64, max($width, $height));
        $this->assertStringNotContainsString('%PDF', $bytes);
        $firstPage = imagecreatefromstring($bytes);
        $this->assertGreaterThan(240, imagecolorsforindex($firstPage, imagecolorat($firstPage, 5, 5))['red']);

        $excerpt = URL::signedRoute('webapp.pieces.score.excerpt', ['piece' => $piece, 'version' => hash('sha256', self::PATH)]);
        $this->get(route('webapp.pieces.score.excerpt', $piece))->assertForbidden();
        $bytes = $this->get($excerpt)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->getContent();
        $strip = imagecreatefromstring($bytes);
        $this->assertEqualsWithDelta(imagesx($strip) * 0.4, imagesy($strip), 1);
        foreach ([0, imagesy($strip) - 1] as $edge) {
            $this->assertGreaterThan(240, imagecolorsforindex($strip, imagecolorat($strip, 5, $edge))['green']);
        }
        $piece->updateQuietly(['score_path' => 'app/score/replaced.pdf']);
        $this->get($excerpt)->assertNotFound();
        $this->get($preview)->assertNotFound();
        Storage::disk('public')->put($piece->score_path, 'invalid PDF');
        $this->get($preview)->assertStatus(503);
    }
}
