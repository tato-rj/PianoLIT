<?php

namespace Tests\Review;

use App\PDF\{EscoreCoverImage, PDFGenerator};
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class EscoreCollectionCoverTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function image($format)
    {
        $image = imagecreatetruecolor(900, 900);
        imagefill($image, 0, 0, imagecolorallocate($image, 40, 90, 120));
        imagefilledrectangle($image, 0, 0, 899, 149, imagecolorallocate($image, 255, 0, 0));
        imagefilledrectangle($image, 0, 750, 899, 899, imagecolorallocate($image, 255, 0, 0));
        ob_start();
        call_user_func('image'.$format, $image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        Storage::disk('public')->put('covers/source.'.$format, $bytes);
        return 'covers/source.'.$format;
    }

    public function test_image_formats_are_center_cropped_without_changing_the_source()
    {
        foreach (['jpeg', 'png', 'webp'] as $format) {
            $path = $this->image($format);
            $hash = hash('sha256', Storage::disk('public')->get($path));
            $image = EscoreCoverImage::fromStorage($path);
            $this->assertNotNull($image);
            $this->assertSame([900, 600], array_slice(getimagesize($image->path()), 0, 2));
            $copy = imagecreatefromjpeg($image->path());
            // Avoid JPEG/WebP chroma bleeding at the removed red-band boundary.
            foreach ([[450, 5], [450, 594]] as $point) {
                $color = imagecolorsforindex($copy, imagecolorat($copy, $point[0], $point[1]));
                $this->assertEqualsWithDelta(40, $color['red'], 8);
                $this->assertEqualsWithDelta(90, $color['green'], 8);
                $this->assertEqualsWithDelta(120, $color['blue'], 8);
            }
            imagedestroy($copy);
            $temporary = $image->path();
            $image->delete();
            $this->assertFileDoesNotExist($temporary);
            $this->assertSame($hash, hash('sha256', Storage::disk('public')->get($path)));
        }
    }

    private function generator()
    {
        return new class extends PDFGenerator {
            public $imagePath;
            protected function frontMatter($entries)
            {
                $this->imagePath = $this->content['cover_image'];
                return parent::frontMatter($entries);
            }
        };
    }

    public function test_collection_image_is_embedded_in_cover_only_and_temporary_copy_is_removed()
    {
        $path = $this->image('png');
        $hash = hash('sha256', Storage::disk('public')->get($path));
        foreach (['letter', 'a4'] as $size) {
            $generator = $this->generator();
            $pdf = $generator->pieces(collect())->request(['title' => 'Image focus', 'subtitle' => 'A collection of pieces', 'color' => '#c4e8dc', 'cover_style' => 'modern', 'page_size' => $size, 'title_page' => false, 'include_edition' => true])->collectionCover($path)->generate()->output();
            $reader = new Fpdi();
            $this->assertSame(3, $reader->setSourceFile(StreamReader::createByString($pdf)));
            $this->assertSame(1, substr_count($pdf, '/Subtype /Image'));
            $this->assertFileDoesNotExist($generator->imagePath);
            $this->assertSame(['cover' => 1, 'title' => 0, 'index' => 1, 'edition' => 1, 'blank' => 0], $generator->metadata()['sections']);
            $this->assertSame($hash, hash('sha256', Storage::disk('public')->get($path)));
            if ($destination = getenv('ESCORE_IMAGE_PREVIEW_DIR')) file_put_contents($destination.'/'.$size.'.pdf', $pdf);
        }
    }

    public function test_no_collection_image_and_missing_or_invalid_images_keep_the_existing_cover()
    {
        Storage::disk('public')->put('invalid.png', 'Not an image');
        foreach ([null, 'missing.jpg', 'invalid.png'] as $path) {
            $generator = $this->generator();
            $pdf = $generator->pieces(collect())->request(['title' => 'Plain cover', 'cover_image' => '/etc/passwd', 'cover_style' => 'modern', 'page_size' => 'letter', 'title_page' => false])->collectionCover($path)->generate()->output();
            $this->assertNull($generator->imagePath);
            $this->assertSame(0, substr_count($pdf, '/Subtype /Image'));
        }
    }

    public function test_failed_composition_removes_the_temporary_collection_image()
    {
        $path = $this->image('jpeg');
        $generator = new class extends PDFGenerator {
            public $imagePath;
            protected function frontMatter($entries)
            {
                $this->imagePath = $this->content['cover_image'];
                throw new \RuntimeException('Composition failed');
            }
        };
        try {
            $generator->pieces(collect())->request(['title' => 'Book'])->collectionCover($path)->generate();
            $this->fail('Composition should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Composition failed', $exception->getMessage());
            $this->assertFileDoesNotExist($generator->imagePath);
            $this->assertTrue(Storage::disk('public')->exists($path));
        }
    }
}
