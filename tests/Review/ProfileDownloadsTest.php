<?php

namespace Tests\Review;

use App\User;
use App\Infograph\Infograph;
use App\Merchandise\Purchase;
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProfileDownloadsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['activity.enabled' => false, 'attribution.enabled' => false]);
        $this->withoutMiddleware([\App\Http\Middleware\UpdateLocation::class]);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    private function account()
    {
        return Model::withoutEvents(function () { return create(User::class, ['first_name' => 'Jamie', 'last_name' => 'Parker', 'email' => 'jamie@example.test']); });
    }

    private function resource($user, $class, $title, $attributes = [], $purchase = [])
    {
        return Model::withoutEvents(function () use ($user, $class, $title, $attributes, $purchase) {
            $item = create($class, $attributes + ($class === Infograph::class ? ['name' => $title, 'cover_path' => 'infograph/test.jpg'] : ['title' => $title, 'pdf_path' => 'books/test.pdf']));
            return Purchase::create($purchase + ['user_id' => $user->id, 'item_type' => get_class($item), 'item_id' => $item->id]);
        });
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    private function preview($response, $name)
    {
        if ($directory = getenv('DOWNLOADS_PREVIEW_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0700, true);
            file_put_contents($directory.'/'.$name.'.html', $response->getContent());
        }
    }

    public function test_download_urls_redirect_before_auth_and_all_named_links_return_to_main()
    {
        foreach (['', 'my.', 'shop.', 'learn.', 'blog.', 'games.', 'metronome.', 'www.', 'admin.'] as $prefix) {
            $this->get('http://'.$prefix.'localhost/my-profile/purchases?page=2&tag=a%2Bb&tag=c')
                ->assertStatus(301)->assertRedirect('http://localhost/profile/downloads?page=2&tag=a%2Bb&tag=c');
            if ($prefix) $this->call('HEAD', 'http://'.$prefix.'localhost/profile/downloads?page=2&tag=a%2Bb&tag=c')
                ->assertStatus(301)->assertRedirect('http://localhost/profile/downloads?page=2&tag=a%2Bb&tag=c');
            $this->assertSame('http://localhost/profile/downloads', route('users.purchases'));
        }
        config(['app.url' => 'localhost']);
        $this->assertSame('http://localhost/profile/downloads', route('users.purchases'));
        $this->withExceptionHandling()->get('http://localhost/profile/downloads')->assertRedirect('http://localhost/login');
    }

    public function test_library_is_owned_by_the_session_and_keeps_formats_costs_and_accessible_accordions()
    {
        $user = $this->account();
        $other = Model::withoutEvents(function () { return create(User::class); });
        $this->resource($other, eBook::class, 'Private other account book');
        $this->resource($user, Infograph::class, "Beethoven's 3 Periods", [], ['created_at' => '2026-10-26 12:00:00']);
        $book = $this->resource($user, eBook::class, 'The Book of Piano Techniques and how to master them', ['epub_path' => 'books/test.epub', 'price' => 0], ['cost' => 12, 'created_at' => '2026-08-17 12:00:00']);
        $this->resource($user, eScore::class, 'Piano Solos Book 1', [], ['cost' => 0, 'created_at' => '2024-12-13 12:00:00']);
        $response = $this->actingAs($user, 'web')->get(route('users.purchases').'?user_id='.$other->id)->assertOk()
            ->assertViewIs('users.purchases.index')->assertSee('My downloads')->assertSee('Infographic')->assertSee('Download PDF')->assertSee('Download ePUB')
            ->assertSee('$12.00')->assertSee('Free')->assertDontSee('Private other account book')->assertDontSee('public-breadcrumb');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//h1[normalize-space(.)="My downloads"]'));
        $this->assertCount(0, $xpath->query('//h1[normalize-space(.)="My profile"]'));
        $this->assertCount(0, $xpath->query('//nav[@id="profile-navigation"]'));
        $this->assertCount(3, $xpath->query('//button[@data-bs-toggle="collapse" and @aria-expanded="false"]'));
        foreach ($xpath->query('//button[@data-bs-toggle="collapse"]') as $button) {
            $id = $button->getAttribute('aria-controls');
            $this->assertCount(1, $xpath->query('//*[@id="'.$id.'" and @aria-labelledby="'.$id.'-heading"]'));
        }
        $links = $xpath->query('//a[contains(@href,"/shop/'.$book->id.'/download")]');
        $this->assertCount(2, $links);
        $paths = [];
        foreach ($links as $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            $paths[] = decrypt($query['path']);
        }
        $this->assertSame(['books/test.pdf', 'books/test.epub'], $paths);
        Storage::fake('public');
        Storage::disk('public')->put('books/test.pdf', 'Original PDF bytes');
        $download = $this->get($links->item(0)->getAttribute('href'))->assertOk()->assertDownload('test.pdf');
        $this->assertSame('Original PDF bytes', $download->streamedContent());
        $this->preview($response, 'downloads');
    }

    public function test_pagination_keeps_eight_newest_resources_and_query_values()
    {
        $user = $this->account();
        for ($i = 1; $i <= 10; $i++) $this->resource($user, Infograph::class, 'Guide '.$i, [], ['created_at' => now()->subDays($i)]);
        $response = $this->actingAs($user, 'web')->get(route('users.purchases').'?source=library')->assertOk();
        $this->assertCount(8, $response->viewData('purchases'));
        $response->assertSee('Showing 1-8 of 10 resources')->assertSee('source=library&amp;page=2', false)->assertDontSee('>Guide 9<', false);
        $response = $this->get(route('users.purchases').'?source=library&page=2')->assertOk()->assertSee('Guide 9')->assertSee('Guide 10');
        $this->assertCount(2, $response->viewData('purchases'));
        $this->get(route('users.purchases').'?page=99')->assertOk()->assertSee('Return to your library')->assertDontSee('Your library starts here');
    }

    public function test_empty_and_removed_resources_have_clear_states_and_downloads_stays_out_of_the_profile_menu()
    {
        $user = $this->account();
        $empty = $this->actingAs($user, 'web')->get(route('users.purchases'))->assertOk()->assertSee('Your library starts here')
            ->assertSee(route('resources.infographs.index'))->assertSee(route('ebooks.index'))->assertDontSee('downloads-list');
        $this->preview($empty, 'empty');
        Model::withoutEvents(function () use ($user) {
            Purchase::create(['user_id' => $user->id, 'item_id' => 999999, 'item_type' => eBook::class]);
        });
        $this->get(route('users.purchases'))->assertOk()->assertSee('Resource unavailable')->assertSee('This file is currently unavailable.');
        $profile = $this->get(route('users.profile'))->assertOk()->assertViewIs('users.profile.index')->assertSee('href="http://localhost/profile/downloads"', false);
        $xpath = $this->xpath($profile);
        $this->assertCount(4, $xpath->query('//nav[@data-section-navigation]//a[starts-with(@href,"#")]'));
        $this->assertCount(0, $xpath->query('//nav[@id="profile-navigation"]//a[contains(@href,"/downloads")]'));
        $this->preview($profile, 'profile');
    }
}
