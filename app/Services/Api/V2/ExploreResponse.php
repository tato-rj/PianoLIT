<?php

namespace App\Services\Api\V2;

use App\Blog\Post;
use App\{Composer, Piece, Tag, Tutorial, User};
use App\Services\Discover\Artwork;
use App\Services\WebApp\PieceCards;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExploreResponse
{
    private const CATEGORIES = ['technique' => 'Technique', 'mood' => 'Mood', 'genre' => 'Genre'];
    private const PAGE_SIZE = 24;

    public function __construct(private DiscoverResponse $summary, private PieceAccess $access)
    {
    }

    public function data(User $user): array
    {
        $tags = $this->selectableTags()->withCount(['pieces' => fn (Builder $query) => $query->has('composer')])
            ->webStyleOrder()->get()->where('pieces_count', '>', 0);
        $categories = [];
        foreach (self::CATEGORIES as $type => $label) {
            $items = $tags->where('type', $type)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            if ($items->isNotEmpty()) {
                $categories[] = ['kind' => 'category', 'id' => $type, 'name' => $label,
                    'tags' => $items->map(fn (Tag $tag) => $this->tag($tag, 'tag'))->all()];
            }
        }

        $highlights = $this->pieceQuery('highlights')->limit(16)->get();
        $composer = (Piece::free()->has('composer')->first() ?? $highlights->first())?->composer;
        $original = Post::published()->where('published_at', '<=', now())->reorder('published_at', 'desc')->orderByDesc('id')->first();
        $periods = $tags->where('type', 'period')->values();
        $levels = $tags->whereIn('type', ['level', 'sublevel'])->sortBy([
            ['order', 'asc'], ['id', 'asc'],
        ])->values();
        $lessons = Tutorial::byType('harmonic analysis')->whereHas('piece.composer')
            ->whereNotNull('video_url')->where('video_url', '!=', '')
            ->latest()->orderByDesc('id')->with('piece.tags')->limit(12)->get()
            ->unique('piece_id')->take(4)->values();

        return ['sections' => array_values(array_filter([
            $this->section('categories', 'Categories', $categories),
            $this->section('highlights', 'Highlights', $this->summaries($highlights),
                $this->resultsTarget('highlights') + ['label' => 'View all']),
            $this->section('originals', 'Originals', $original ? [$this->original($original)] : []),
            $this->section('periods', 'Periods & Styles', $periods->map(fn (Tag $tag) => $this->tag($tag, 'period') + [
                'artwork_url' => $tag->web_cover_image,
            ])->all()),
            $this->section('levels', 'Levels', $levels->map(fn (Tag $tag) => $this->tag($tag, 'level'))->all()),
            $this->section('featured_composer', 'Featured composer', $composer ? [$this->featuredComposer($composer)] : []),
            $this->section('harmony', 'Harmony', $lessons->map(fn (Tutorial $lesson) => $this->harmony($lesson, $user))->all()),
        ]))];
    }

    public function pieces(string $scope, array $ids, int $page): array
    {
        $tags = $this->selectableTags()->whereKey(array_values($ids))->get();
        // Compare exact decimal strings, including IDs outside the database integer range.
        if (array_diff($ids, $tags->modelKeys())) {
            throw ValidationException::withMessages(['tag_ids' => 'Choose tags from Explore.']);
        }
        $query = $this->pieceQuery($scope);
        foreach ($tags->groupBy(fn (Tag $tag) => $tag->type === 'sublevel' ? 'level' : $tag->type) as $group) {
            $query->whereHas('tags', fn (Builder $query) => $query->whereIn('tags.id', $group->modelKeys()));
        }
        $results = $query->simplePaginate(self::PAGE_SIZE, ['pieces.*'], 'page', $page);

        return [
            'pieces' => $this->summaries($results->getCollection()),
            'page' => $page,
            'next_page' => $page < 10000 && $results->hasMorePages() ? $page + 1 : null,
        ];
    }

    private function selectableTags(): Builder
    {
        return Tag::where(function (Builder $query) {
            $query->whereIn('type', array_merge(array_keys(self::CATEGORIES), ['period']))
                ->orWhere(fn (Builder $levels) => $levels->extendedLevels()->reorder());
        });
    }

    private function pieceQuery(string $scope): Builder
    {
        $query = Piece::has('composer')->withCount([]);
        if ($scope === 'highlights') {
            return $query->freePicks()->free(false)->where('highlighted_at', '<=', now())->orderByDesc('pieces.id');
        }

        return $query->latest('pieces.created_at')->orderByDesc('pieces.id');
    }

    private function summaries($pieces): array
    {
        return PieceCards::load($pieces, false)->map(function (Piece $piece) {
            $piece->discover_artwork_url = Artwork::forPiece($piece);
            return $this->summary->piece($piece);
        })->values()->all();
    }

    private function section(string $id, string $title, array $items, ?array $action = null): ?array
    {
        if (!$items) return null;

        $section = ['id' => $id, 'kind' => $id, 'title' => $title, 'items' => $items];
        if ($action) $section['action'] = $action;

        return $section;
    }

    private function tag(Tag $tag, string $kind): array
    {
        return [
            'kind' => $kind, 'id' => (string) $tag->id, 'name' => Str::ucfirst($tag->name),
            'piece_count' => (int) $tag->pieces_count,
            'target' => $this->resultsTarget('catalogue', [(string) $tag->id]),
        ];
    }

    private function resultsTarget(string $scope, array $ids = []): array
    {
        return ['kind' => 'explore_results', 'scope' => $scope, 'tag_ids' => $ids];
    }

    private function original(Post $post): array
    {
        return [
            'kind' => 'original', 'id' => (string) $post->id, 'title' => $post->title,
            'description' => $this->text($post->description), 'artwork_url' => $post->cover_path ? $post->cover_image : null,
            'target' => ['kind' => 'article', 'id' => (string) $post->id, 'query' => null, 'web_url' => $post->app_url],
        ];
    }

    private function harmony(Tutorial $lesson, User $user): array
    {
        $piece = $lesson->piece;
        return [
            'kind' => 'harmony_lesson', 'id' => (string) $lesson->id, 'title' => $piece->medium_name,
            'composer' => $this->summary->composer($piece->composer),
            'description' => $this->text($lesson->description), 'artwork_url' => Artwork::forPiece($piece),
            'access' => $this->access->forUser($user, $piece),
            'target' => ['kind' => 'piece_lesson', 'piece_id' => (string) $piece->id, 'video_id' => (string) $lesson->id],
        ];
    }

    private function featuredComposer(Composer $composer): array
    {
        $card = $this->summary->composer($composer);
        $card['target']['kind'] = 'composer_profile';
        $card['target']['web_url'] = route('webapp.composers.show', $composer);

        return $card + $this->summary->composerProfileFields($composer) + [
            'curiosity' => $this->text($composer->curiosity),
        ];
    }

    private function text(?string $value): ?string
    {
        $text = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return $text === '' ? null : $text;
    }
}
