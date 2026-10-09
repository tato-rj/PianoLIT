<?php

namespace Tests\Review;

use App\{Piece, Tag};
use App\Services\WebApp\MatchTour;
use Illuminate\Filesystem\Filesystem;

class PeriodArtworkTest extends ReviewTestCase
{
    private $publicDirectory;

    public function setUp(): void
    {
        parent::setUp();
        $this->publicDirectory = sys_get_temp_dir().'/pianolit-period-artwork-'.bin2hex(random_bytes(8));
        $this->app->instance('path.public', $this->publicDirectory);
        $folder = $this->publicDirectory.'/images/backgrounds/periods/romantic';
        mkdir($folder, 0777, true);
        foreach (['01.jpg', 'Artwork (2).WEBP', 'notes.txt'] as $name) {
            file_put_contents($folder.'/'.$name, 'fixture');
        }
        mkdir($folder.'/directory.jpg');
    }

    public function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->publicDirectory);
        parent::tearDown();
    }

    public function test_web_period_and_genre_artwork_is_drawn_from_images_each_time()
    {
        $allowed = [
            asset('images/backgrounds/periods/romantic/01.jpg'),
            asset('images/backgrounds/periods/romantic/Artwork%20%282%29.WEBP'),
        ];
        foreach (['period', 'genre'] as $type) {
            $tag = new Tag(['type' => $type, 'name' => 'Romantic']);
            $draws = [];
            // Exercise repeat renders of the same model, as with a cached feed.
            for ($i = 0; $i < 64; $i++) {
                $image = $tag->web_cover_image;
                $this->assertContains($image, $allowed);
                $draws[] = $image;
            }
            $this->assertCount(2, array_unique($draws));
        }
    }

    public function test_web_artwork_can_exclude_used_images_without_repeating_exhausted_choices()
    {
        $tag = new Tag(['type' => 'period', 'name' => 'romantic']);
        $first = $tag->webCoverImageExcept();
        $second = $tag->webCoverImageExcept([$first]);
        $this->assertNotNull($second);
        $this->assertNotSame($first, $second);
        $this->assertNull($tag->webCoverImageExcept([$first, $second]));
        $fallback = new Tag(['type' => 'period', 'name' => 'baroque']);
        $this->assertNull($fallback->webCoverImageExcept([$fallback->cover_image]));
    }

    public function test_missing_empty_and_unrelated_folders_use_existing_fallbacks()
    {
        mkdir($this->publicDirectory.'/images/backgrounds/periods/baroque');
        foreach (['baroque', 'classical', '../romantic'] as $name) {
            $tag = new Tag(['type' => 'period', 'name' => $name]);
            $this->assertSame($tag->cover_image, $tag->web_cover_image);
        }
        foreach (['genre', 'mood'] as $type) {
            $tag = new Tag(['type' => $type, 'name' => $type === 'genre' ? 'unknown' : 'romantic']);
            $this->assertNull($tag->web_cover_image);
        }
    }

    public function test_piece_web_fallback_and_mobile_attributes_remain_separate()
    {
        $tag = new Tag(['type' => 'period', 'name' => 'romantic']);
        $piece = new Piece;
        $piece->setRelation('tags', collect([$tag]));
        $this->assertStringContainsString('/periods/romantic/', $piece->web_image_background);
        $this->assertStringContainsString('/periods/romantic/', MatchTour::artwork($piece));
        $this->assertSame(asset('images/backgrounds/periods/romantic.jpg'), $tag->toArray()['cover_image']);
        $this->assertArrayNotHasKey('web_cover_image', $tag->toArray());
        $piece->setAppends(['image_background']);
        $this->assertSame($tag->cover_image, $piece->toArray()['image_background']);
        $this->assertArrayNotHasKey('web_image_background', $piece->toArray());

        $piece->cover_path = 'pieces/custom.jpg';
        $this->assertSame(storage('pieces/custom.jpg'), $piece->web_image_background);
        $this->assertSame($piece->image_background, MatchTour::artwork($piece));
        $piece->cover_path = null;
        $piece->setRelation('tags', collect());
        $this->assertSame(asset('images/webapp/thumbnail.jpg'), $piece->web_image_background);
    }

    public function test_period_circle_uses_the_folder_image()
    {
        $tag = new Tag(['type' => 'period', 'name' => 'romantic']);
        $tag->pieces_count = 10;
        $html = view('webapp.explore.rows.period', ['row' => [
            'collection' => collect([$tag]), 'label' => 'Periods',
        ]])->render();
        $this->assertStringContainsString('/images/backgrounds/periods/romantic/', $html);
        $this->assertStringNotContainsString('/images/backgrounds/periods/romantic.jpg', $html);
    }
}
