<?php

namespace Tests\Review;

use App\Http\Controllers\UsersController;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;

class GiftDownloadRegressionTest extends ReviewTestCase
{
    public function test_downloads_stay_inside_gift_directories_even_with_traversal_or_symlinks()
    {
        $directory = sys_get_temp_dir().'/pianolit-gift-'.bin2hex(random_bytes(8));
        mkdir($directory.'/public/images/gifts', 0777, true);
        $default = $directory.'/public/images/gifts/circle-of-fifths.jpg';
        $gift = $directory.'/public/images/gifts/allowed.pdf';
        file_put_contents($default, 'default gift');
        file_put_contents($gift, 'allowed gift');
        file_put_contents($directory.'/private.txt', 'private fixture');
        symlink($directory.'/private.txt', $directory.'/public/images/gifts/link.jpg');
        $this->app->instance('path.public', $directory.'/public');

        try {
            foreach (['../private.txt', 'images/gifts/../../../private.txt', 'images/gifts/link.jpg', ['invalid'], "bad\0path"] as $path) {
                $this->app->instance('request', new Request(['gift' => $path]));
                $response = (new UsersController)->gift();
                $this->assertSame($default, $response->getFile()->getPathname());
            }

            $this->app->instance('request', new Request(['gift' => 'images/gifts/allowed.pdf']));
            $this->assertSame(realpath($gift), (new UsersController)->gift()->getFile()->getPathname());
        } finally {
            (new Filesystem)->deleteDirectory($directory);
        }
    }
}
