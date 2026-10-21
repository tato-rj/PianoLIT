<?php

namespace Tests\Review;

use App\Http\Controllers\ToolsController;

class StaffGeneratorRetirementTest extends ReviewTestCase
{
    public function test_the_generator_and_all_sheet_download_routes_are_retired()
    {
        $this->assertNull(app('router')->getRoutes()->getByName('tools.staff'));
        $this->assertFalse(method_exists(ToolsController::class, 'staff'));
        $this->assertFalse(view()->exists('tools.staff.index'));

        $this->withExceptionHandling();
        foreach (['localhost', 'www.localhost', 'blog.localhost', 'shop.localhost', 'learn.localhost'] as $host) {
            foreach (['/tools/staff', '/tools/staff/blank', '/tools/staff/piano', '/tools/staff/treble', '/tools/staff/bass'] as $path) {
                $this->get('http://'.$host.$path)->assertNotFound();
                $this->get('http://'.$host.$path.'?size=xl')->assertNotFound();
            }
            $this->call('HEAD', 'http://'.$host.'/tools/staff/piano?size=xl')->assertNotFound();
        }
    }

    public function test_the_dedicated_sheet_assets_are_not_shipped_or_published()
    {
        foreach (['resources/images', 'public/images'] as $directory) {
            $this->assertDirectoryDoesNotExist(base_path($directory.'/sheets'));
            $this->assertFileDoesNotExist(base_path($directory.'/misc/thumbnails/staff.jpg'));
        }

        $manifest = json_decode(file_get_contents(public_path('mix-manifest.json')), true);
        $this->assertArrayNotHasKey('/images/misc/thumbnails/staff.jpg', $manifest);
        $this->assertSame([], array_values(array_filter(array_keys($manifest), function ($path) {
            return strpos($path, '/images/sheets/') === 0;
        })));
    }
}
