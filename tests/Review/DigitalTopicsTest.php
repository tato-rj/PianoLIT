<?php

namespace Tests\Review;

use App\Admin;
use App\Shop\{eBook, eScore, eBookTopic, eScoreTopic};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class DigitalTopicsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
    }

    public static function kinds(): array
    {
        return [['ebooks', eBook::class, eBookTopic::class, eScoreTopic::class],
            ['escores', eScore::class, eScoreTopic::class, eBookTopic::class]];
    }

    private function topic(string $class, string $name = 'Practice', array $extra = [])
    {
        return $class::create($extra + ['name' => $name, 'slug' => str_slug($name), 'creator_id' => auth('admin')->id()]);
    }

    /** @dataProvider kinds */
    public function test_index_has_shared_topic_forms_and_ordered_shop_menu($kind, $class, $topicClass, $otherClass)
    {
        $topic = $this->topic($topicClass, 'Practice <script>');
        $this->topic($otherClass, 'Other product family');
        $response = $this->get(route('admin.'.$kind.'.index'))->assertOk()
            ->assertSee('Manage topics')->assertSee('digital-topics-modal')->assertSee('Practice &lt;script&gt;', false)
            ->assertDontSee('Other product family')->assertDontSee('eBook Topics')->assertDontSee('eScore Topics');
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertCount(0, $xpath->query('//form//form'));
        $this->assertCount(0, $xpath->query('//form[@method="POST" and not(.//input[@name="_token"])]'));
        $this->assertCount(1, $xpath->query('//*[@id="digital-topic-'.$topic->id.'"]'));
        $this->assertSame(['Merchandise', 'eBooks', 'eScores', '', 'Orders'], array_map(fn ($node) => trim($node->textContent), iterator_to_array($xpath->query('//*[@id="shop"]/li'))));
        $this->assertCount(1, $xpath->query('//*[@id="shop"]/li[4][@class="admin-submenu-divider"]'));
        $this->get(route('admin.'.$kind.'.topics.index', ['search' => 'piano', 'page' => 2]))
            ->assertRedirect(route('admin.'.$kind.'.index', ['search' => 'piano', 'page' => 2, 'topics' => '1']));
        $this->withExceptionHandling();
        $this->getJson(route('admin.'.$kind.'.index', ['topics' => ['1']]))->assertUnprocessable();
        Http::assertNothingSent();
        if ($directory = getenv('DIGITAL_TOPICS_PREVIEW_DIR')) {
            $topic->updateQuietly(['name' => 'Practice', 'slug' => 'practice']);
            $this->topic($topicClass, 'Sight-reading');
            $this->topic($topicClass, 'Theory');
            $product = Model::withoutEvents(fn () => create($class, ['title' => 'Piano collection', 'cover_path' => null, 'mockup_path' => null]));
            $product->topics()->attach($topic);
            foreach ([$kind => route('admin.'.$kind.'.index', ['topics' => '1']), $kind.'-edit' => route('admin.'.$kind.'.edit', $product)] as $name => $url) {
                $html = $this->get($url)->assertOk()->getContent();
                $html = str_replace('http://admin.localhost', 'http://127.0.0.1:8767', $html);
                file_put_contents($directory.'/'.$name.'.html', $html);
            }
        }
    }

    /** @dataProvider kinds */
    public function test_create_rename_delete_are_scoped_and_preserve_directory_state($kind, $class, $topicClass, $otherClass)
    {
        $topic = $this->topic($topicClass);
        $other = $this->topic($otherClass);
        $product = Model::withoutEvents(fn () => create($class));
        $product->topics()->attach($topic);
        $filters = ['search' => 'piano', 'status' => 'draft', 'sort' => 'title_asc', 'page' => 2];
        $returnUrl = route('admin.'.$kind.'.index', ['search' => 'piano', 'sort' => 'title_asc', 'status' => 'draft', 'page' => 2, 'topics' => '1']);
        $this->post(route('admin.'.$kind.'.topics.store', $filters), ['name' => '  Sight-reading  '])->assertRedirect($returnUrl)->assertSessionHas('status', 'Topic created.');
        $created = $topicClass::where('name', 'Sight-reading')->firstOrFail();
        $this->assertSame(auth('admin')->id(), $created->creator_id);
        $this->patch(route('admin.'.$kind.'.topics.update', ['topic' => $topic] + $filters), ['name' => 'Theory'])
            ->assertRedirect($returnUrl)->assertSessionHas('status', 'Topic saved.');
        $this->assertSame('theory', $topic->fresh()->slug);
        $this->assertSame([$topic->id], $product->topics()->pluck($topic->getTable().'.id')->all());
        $this->assertSame('Practice', $other->fresh()->name);
        $this->delete(route('admin.'.$kind.'.topics.destroy', ['topic' => $topic->fresh()] + $filters))
            ->assertRedirect($returnUrl)->assertSessionHas('status', 'Topic deleted.');
        $this->assertNull($topic->fresh());
        $this->assertNotNull($product->fresh());
        $this->assertSame(0, $product->topics()->count());
        $this->assertNotNull($other->fresh());
        $this->assertNotNull($created->fresh());
        Http::assertNothingSent();
    }

    /** @dataProvider kinds */
    public function test_validation_returns_to_the_modal_and_only_marks_the_failed_form($kind, $class, $topicClass, $otherClass)
    {
        $topic = $this->topic($topicClass);
        $second = $this->topic($topicClass, 'Theory');
        $this->withExceptionHandling();
        $filters = ['search' => 'piano'];
        $this->post(route('admin.'.$kind.'.topics.store', $filters), ['name' => 'Practice'])
            ->assertRedirect(route('admin.'.$kind.'.index', $filters + ['topics' => '1']))->assertSessionHasErrors('name', null, 'digitalTopicCreate');
        $this->get(route('admin.'.$kind.'.index', ['topics' => '1']))->assertOk()->assertSee('id="digital-topic-new"', false);
        $this->patch(route('admin.'.$kind.'.topics.update', ['topic' => $second] + $filters), ['name' => 'Practice'])
            ->assertRedirect(route('admin.'.$kind.'.index', $filters + ['topics' => '1']))->assertSessionHasErrors('name', null, 'digitalTopic'.$second->id);
        $response = $this->get(route('admin.'.$kind.'.index', ['topics' => '1']))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//input[contains(@class,"is-invalid")]'));
        $this->assertSame('digital-topic-'.$second->id, $xpath->query('//input[contains(@class,"is-invalid")]')->item(0)->getAttribute('id'));
        $this->assertSame('Theory', $second->fresh()->name);
        foreach (['', ['bad'], str_repeat('x', 256), '!!!', 'Practice', 'Practice!'] as $name) {
            $this->postJson(route('admin.'.$kind.'.topics.store'), compact('name'))->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->patch(route('admin.'.$kind.'.topics.update', $topic), ['name' => 'Practice'])->assertSessionHasNoErrors();
    }

    /** @dataProvider kinds */
    public function test_empty_state_authentication_and_family_route_binding($kind, $class, $topicClass, $otherClass)
    {
        $this->get(route('admin.'.$kind.'.index'))->assertOk()->assertSee('No topics yet.');
        $foreign = $this->topic($otherClass, 'Foreign-only topic');
        $this->withExceptionHandling();
        $this->patch(route('admin.'.$kind.'.topics.update', $foreign), ['name' => 'Wrong'])->assertNotFound();
        $this->delete(route('admin.'.$kind.'.topics.destroy', $foreign))->assertNotFound();
        $this->assertSame('Foreign-only topic', $foreign->fresh()->name);
        auth('admin')->logout();
        $this->post(route('admin.'.$kind.'.topics.store'), ['name' => 'Unauthenticated'])->assertRedirect(route('admin.login.show'));
        $this->get(route('admin.'.$kind.'.topics.index'))->assertRedirect(route('admin.login.show'));
        $this->assertSame(0, $topicClass::count());
        foreach (['store', 'update', 'destroy'] as $action) {
            $middleware = app('router')->getRoutes()->getByName('admin.'.$kind.'.topics.'.$action)->gatherMiddleware();
            $this->assertContains('web', $middleware);
            $this->assertContains('auth:admin', $middleware);
        }
    }
}
