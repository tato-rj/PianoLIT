<?php

namespace Tests\Review;

use App\Blog\Post;
use App\Services\Blog\ArticleContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class BlogArticleLinksTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
    }

    private function content(): string
    {
        return <<<'HTML'
<h2 id="music">Liszt, études &amp; récitals</h2>
<p><a href="https://www.academia.edu/85719336/Origins_of_the_Piano_Recital_in_England_1830_1870">Their study of concert programs</a></p>
<p><a href="https://example.test/?first=1&amp;second=2" target="_self" rel="nofollow sponsored opener">External source</a></p>
<p><a href="https://pianolit.com/support" target="_parent">PianoLIT</a> <a href="/another-post">Relative post</a> <a href="#music">Section</a></p>
<p><a href="mailto:hello@example.test">Email</a> <a href="//example.test/source" target="article" rel="NOOPENER noreferrer nofollow">Source</a></p>
<figure><a href="https://example.test/score.pdf" download><img src="/score.jpg" alt="Étude score"></a><figcaption>A score.</figcaption></figure>
<table><tbody><tr><td><A HREF=/table-source TARGET=_top>Table source</A></td></tr></tbody></table>
<p><a name="bookmark">Bookmark</a></p>
<audio controls src="/storage/blog/audio/recital.mp3"></audio>
<iframe src="https://www.youtube.com/embed/example" allowfullscreen></iframe>
HTML;
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<meta charset="UTF-8">'.$html);
        return new \DOMXPath($document);
    }

    private function assertArticleLinks(\DOMXPath $xpath, string $container): void
    {
        $links = $xpath->query($container.'//a[@href]');
        $this->assertCount(9, $links);
        foreach ($links as $link) {
            $this->assertSame('_blank', $link->getAttribute('target'), $link->getAttribute('href'));
            $relations = explode(' ', $link->getAttribute('rel'));
            $this->assertContains('noopener', $relations);
            $this->assertContains('noreferrer', $relations);
            $this->assertNotContains('opener', $relations);
            $this->assertSame(array_values(array_unique($relations)), $relations);
        }
        $this->assertSame(2, $xpath->query($container.'//a[contains(concat(" ", @rel, " "), " nofollow ")]')->length);
        $this->assertSame(1, $xpath->query($container.'//a[contains(concat(" ", @rel, " "), " sponsored ")]')->length);
        $this->assertSame(0, $xpath->query($container.'//a[@name="bookmark"]/@target')->length);
        $this->assertSame(1, $xpath->query($container.'//a[@download]/img[@alt="Étude score"]')->length);
        $this->assertSame(1, $xpath->query($container.'//audio[@controls and @src="/storage/blog/audio/recital.mp3"]')->length);
        $this->assertSame(1, $xpath->query($container.'//iframe[@allowfullscreen and @src="https://www.youtube.com/embed/example"]')->length);
        $this->assertSame('Liszt, études & récitals', $xpath->query($container.'//h2')->item(0)->textContent);
        $this->assertSame('https://example.test/?first=1&second=2', $links->item(1)->getAttribute('href'));
    }

    public function test_all_public_article_links_open_new_tabs_without_changing_saved_or_mobile_content()
    {
        $post = Model::withoutEvents(fn () => create(Post::class, [
            'title' => 'Article link behavior', 'content' => $this->content(), 'published_at' => now(),
        ]));
        $before = $post->fresh()->getAttributes();
        $jsonKeys = array_keys($post->fresh()->toArray());

        $response = $this->get(route('posts.show', $post))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertArticleLinks($xpath, '//*[@id="blog-content"]');
        $this->assertSame(1, $xpath->query('//a[@aria-label="PianoLIT home" and not(@target)]')->length);
        $this->assertSame($before, $post->fresh()->getAttributes());
        $this->assertSame($jsonKeys, array_keys($post->fresh()->toArray()));
        $this->assertSame($this->content(), $post->fresh()->toArray()['content']);

        $this->get(route('posts.app', $post))->assertOk()->assertSee($this->content(), false);
    }

    public function test_legacy_browser_view_uses_the_same_article_link_behavior()
    {
        $post = Model::withoutEvents(fn () => create(Post::class, [
            'title' => 'Legacy article links', 'content' => $this->content(), 'published_at' => now(),
        ]));
        $html = view('webapp.blog.show', ['post' => $post, 'errors' => new \Illuminate\Support\ViewErrorBag])->render();
        $this->assertArticleLinks($this->xpath($html), '//*[@id="blog-post"]');
    }

    public function test_empty_or_linkless_content_is_preserved_and_rendering_is_repeatable()
    {
        $renderer = new ArticleContent;
        $this->assertSame('', $renderer->render(null));
        $this->assertSame('', $renderer->render(''));
        foreach (['<p>Études &amp; récitals</p>', '<article><a name="bookmark">Bookmark</a></article>'] as $html) {
            $this->assertSame($html, $renderer->render($html));
        }
        $rendered = $renderer->render($this->content());
        $this->assertSame($rendered, $renderer->render($rendered));
    }
}
