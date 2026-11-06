<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Services\Admin\DigitalProductsTable;
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Http, Storage};

class AdminDigitalProductsTest extends ReviewTestCase
{
    private $products;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        Model::withoutEvents(function () {
            foreach (self::kinds() as [$kind, $class]) {
                $this->products[$kind] = collect();
                for ($i = 0; $i < 15; $i++) {
                    $product = create($class, ['title' => sprintf('Piano collection %02d', $i), 'author' => 'Clara Example',
                        'subtitle' => 'Short piano pieces', 'created_at' => now()->subDays(15 - $i),
                        'published_at' => $i % 3 ? null : now()->subDays(15 - $i),
                        'cover_path' => null, 'mockup_path' => null, 'previews' => serialize([])]);
                    $this->products[$kind]->push($product);
                    for ($j = 0; $j < $i; $j++) $product->purchases()->create(['user_id' => 99999, 'cost' => $j % 2 ? 12 : null]);
                }
            }
        });
    }

    public static function kinds(): array { return [['ebooks', eBook::class], ['escores', eScore::class]]; }

    /** @dataProvider kinds */
    public function test_shared_table_filters_sorts_and_native_pagination($kind, $class)
    {
        $response = $this->get(route('admin.'.$kind.'.index'))->assertOk()->assertSee('Apply filters')
            ->assertSee('class="admin-table__table ', false)->assertDontSee('cdn.datatables.net')->assertDontSee('new DataTable(');
        $this->savePreview($kind, $response);
        $items = $response->viewData('products');
        $this->assertSame(12, $items->perPage());
        $this->assertSame(15, $items->total());
        $this->assertSame($this->products[$kind]->reverse()->take(12)->pluck('id')->values()->all(), $items->pluck('id')->all());
        $this->assertSame(14, $items->first()->purchases_count);
        $this->assertSame(5, $this->get(route('admin.'.$kind.'.index', ['status' => 'published']))->assertOk()->viewData('products')->total());
        $this->assertSame(10, $this->get(route('admin.'.$kind.'.index', ['status' => 'draft']))->assertOk()->viewData('products')->total());
        foreach (DigitalProductsTable::SORTS as $sort => $label) {
            $items = $this->get(route('admin.'.$kind.'.index', compact('sort')))->assertOk()->viewData('products');
            $this->assertCount(12, $items);
            $this->assertSame($sort === 'published_asc' ? $this->products[$kind][1]->id
                : ($sort === 'published_desc' ? $this->products[$kind][12]->id
                : (substr($sort, -4) === 'desc' ? $this->products[$kind]->last()->id : $this->products[$kind]->first()->id)), $items->first()->id, $sort);
        }
        $items = $this->get(route('admin.'.$kind.'.index', ['sort' => 'title_asc', 'search' => 'collection', 'page' => 2]))->assertOk()->viewData('products');
        $this->assertCount(3, $items);
        $this->assertSame('Piano collection 12', $items->first()->title);
        $this->assertStringContainsString('search=collection', $items->url(1));
        $this->assertStringContainsString('sort=title_asc', $items->url(1));
        Http::assertNothingSent();
    }

    /** @dataProvider kinds */
    public function test_literal_search_escaping_empty_states_and_web_gallery_artwork($kind, $class)
    {
        $target = $this->products[$kind]->first();
        $target->updateQuietly(['title' => '100% _unique! <script>', 'mockup_path' => 'old.jpg']);
        DB::table('shop_digital_media')->insert(['product_type' => $class, 'product_id' => $target->id, 'revision' => 1,
            'images' => json_encode([['id' => 'one', 'path' => 'curated.jpg', 'removed' => false]]), 'main_image_id' => 'one', 'preview_pages' => '[]']);
        $response = $this->get(route('admin.'.$kind.'.index', ['search' => '% _unique! Clara']))->assertOk()
            ->assertSee('&lt;script&gt;', false)->assertDontSee('100% _unique! <script>', false)->assertSee('curated.jpg')->assertDontSee('old.jpg');
        $this->assertSame(1, $response->viewData('products')->total());
        $this->assertSame(15, $this->get(route('admin.'.$kind.'.index', ['search' => 'Clara Short']))->assertOk()->viewData('products')->total());
        $this->assertContains($target->id, $this->get(route('admin.'.$kind.'.index', ['search' => (string) $target->id]))->assertOk()->viewData('products')->pluck('id')->all());
        $this->get(route('admin.'.$kind.'.index', ['search' => 'absent']))->assertOk()->assertSee('No matching');
        $this->get(route('admin.'.$kind.'.index', ['page' => 100]))->assertOk()->assertSee('on this page')->assertSee('First page');
    }

    /** @dataProvider kinds */
    public function test_purchase_details_are_paginated_owned_and_loaded_only_when_opened($kind, $class)
    {
        $target = $this->products[$kind]->last();
        $buyer = Model::withoutEvents(fn () => create(User::class, ['first_name' => 'Buyer <script>', 'last_name' => 'Example']));
        $target->purchases()->latest('id')->first()->updateQuietly(['user_id' => $buyer->id]);
        $this->get(route('admin.'.$kind.'.index'))->assertOk()->assertDontSee('Buyer &lt;script&gt;', false)->assertViewHas('purchases', null);
        $response = $this->get(route('admin.'.$kind.'.index', ['purchases' => $target->id, 'page' => 2, 'sort' => 'title_asc']))->assertOk()
            ->assertSee('Buyer &lt;script&gt;', false)->assertSee('Account deleted')->assertSee('Paid')->assertSee('Free')->assertSee('autoshow');
        $this->savePreview($kind.'-purchases', $response);
        $purchases = $response->viewData('purchases');
        $this->assertCount(12, $purchases);
        $this->assertSame(14, $purchases->total());
        $this->assertTrue($purchases->every(fn ($purchase) => $purchase->item_type === $class && $purchase->item_id === $target->id));
        $this->assertStringContainsString('page=2', $purchases->url(2));
        $this->assertStringContainsString('purchases_page=2', $purchases->url(2));
        $this->assertStringContainsString('sort=title_asc', $purchases->url(2));
        $this->assertCount(2, $this->get(route('admin.'.$kind.'.index', ['purchases' => $target->id, 'purchases_page' => 2]))->assertOk()->viewData('purchases'));
    }

    /** @dataProvider kinds */
    public function test_directory_publication_and_deletion_preserve_filters_and_clamp_pages($kind, $class)
    {
        $target = $this->products[$kind][1];
        $params = [$kind === 'ebooks' ? 'ebook' : 'escore' => $target, 'return_to' => 'directory', 'status' => 'draft', 'sort' => 'title_desc'];
        $this->patch(route('admin.'.$kind.'.update-status', $params), ['published' => 1])->assertRedirect(route('admin.'.$kind.'.index', ['sort' => 'title_desc', 'status' => 'draft']));
        $publishedAt = $target->fresh()->published_at->toDateTimeString();
        $this->patch(route('admin.'.$kind.'.update-status', $params), ['published' => 1])->assertRedirect();
        $this->assertSame($publishedAt, $target->fresh()->published_at->toDateTimeString());
        $this->patch(route('admin.'.$kind.'.update-status', $params), ['published' => 0])->assertRedirect();
        $this->assertNull($target->fresh()->published_at);
        $this->products[$kind]->take(2)->each->delete();
        $this->delete(route('admin.'.$kind.'.destroy', [$kind === 'ebooks' ? 'ebook' : 'escore' => $this->products[$kind]->last(), 'return_to' => 'directory', 'page' => 2]))
            ->assertRedirect(route('admin.'.$kind.'.index'));
        $this->assertSame(12, $class::count());
        $edit = $this->get(route('admin.'.$kind.'.edit', [$kind === 'ebooks' ? 'ebook' : 'escore' => $this->products[$kind][2], 'search' => 'collection', 'sort' => 'title_asc', 'page' => 2]))->assertOk();
        $edit->assertViewHas('returnFilters', ['search' => 'collection', 'sort' => 'title_asc', 'page' => '2']);
    }

    /** @dataProvider kinds */
    public function test_validation_authentication_and_query_cost($kind, $class)
    {
        $counts = [];
        foreach ([12, 24] as $perPage) {
            DB::enableQueryLog(); DB::flushQueryLog();
            $data = (new DigitalProductsTable)->get(Request::create('/', 'GET', ['per_page' => $perPage]), $kind);
            view('admin.pages.digital.table.rows', $data)->render();
            $queries = DB::getQueryLog(); DB::disableQueryLog();
            $counts[] = count($queries);
            $this->assertStringNotContainsString('from "users"', implode(' ', array_column($queries, 'query')));
        }
        $this->assertSame([4, 4], $counts);
        $this->withExceptionHandling();
        foreach ([['sort' => 'password_asc'], ['status' => ['draft']], ['search' => ['bad']], ['per_page' => 1000], ['page' => -1], ['purchases' => ['bad']], ['purchases_page' => -1]] as $invalid) {
            $this->getJson(route('admin.'.$kind.'.index', $invalid))->assertUnprocessable();
        }
        $target = $this->products[$kind]->first();
        $this->patchJson(route('admin.'.$kind.'.update-status', [$kind === 'ebooks' ? 'ebook' : 'escore' => $target, 'return_to' => 'directory']), ['published' => 'bad'])->assertUnprocessable();
        $this->assertNotNull($target->fresh()->published_at);
        auth('admin')->logout();
        $this->get(route('admin.'.$kind.'.index'))->assertRedirect(route('admin.login.show'));
        $this->get(route('admin.'.$kind.'.index', ['purchases' => $target->id]))->assertRedirect(route('admin.login.show'));
    }

    /** @dataProvider kinds */
    public function test_existing_ajax_list_and_publication_response_remain_available($kind, $class)
    {
        $this->app->forgetInstance('datatables.request');
        $this->getJson(route('admin.'.$kind.'.index', ['draw' => 1, 'start' => 0, 'length' => 12]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 15)->assertJsonCount(12, 'data');
        $target = $this->products[$kind]->first();
        $this->patch(route('admin.'.$kind.'.update-status', $target), [], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('no longer');
        $this->assertNull($target->fresh()->published_at);
    }

    private function savePreview($name, $response): void
    {
        if ($directory = getenv('ADMIN_DIGITAL_PREVIEW_DIR')) {
            $html = preg_replace('~https?://(?:admin\\.)?localhost(?=/(?:css|js|images|fonts|storage)/)~', '/assets', $response->getContent());
            $html = str_replace(['href="/css/', 'src="/js/'], ['href="/assets/css/', 'src="/assets/js/'], $html);
            file_put_contents($directory.'/'.$name.'.html', $html);
        }
    }
}
