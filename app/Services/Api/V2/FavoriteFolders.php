<?php

namespace App\Services\Api\V2;

use App\{Favorite, FavoriteFolder, Piece, User};
use App\Services\WebApp\FavoriteAccess;
use App\Support\FolderPalette;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\Rule;

class FavoriteFolders
{
    const RECEIPT_HOURS = 24;

    public function folderIds(User $user, $pieceId): array
    {
        // Both the saved record and its folder must belong to this account.
        return DB::table('favorites')->join('favorite_folders', 'favorite_folders.id', '=', 'favorites.favorite_folder_id')
            ->where('favorites.user_id', $user->id)->where('favorite_folders.user_id', $user->id)
            ->where('favorites.piece_id', $pieceId)->orderBy('favorite_folders.id')
            ->distinct()->pluck('favorite_folders.id')->map(function ($id) { return (string) $id; })->all();
    }

    public function listing(User $user, string $pieceId): array
    {
        return DB::transaction(function () use ($user, $pieceId) {
            app(FavoriteAccess::class)->lock($user);
            Piece::findOrFail($pieceId);
            $folders = DB::table('favorite_folders')->where('user_id', $user->id)->orderBy('name')->orderBy('id')->get();
            $ids = $this->folderIds($user, $pieceId);
            $counts = DB::table('favorites')->where('user_id', $user->id)->whereIn('favorite_folder_id', $folders->pluck('id'))
                ->selectRaw('favorite_folder_id, COUNT(*) as total')->groupBy('favorite_folder_id')->pluck('total', 'favorite_folder_id');
            return $this->pieceState($pieceId, $ids) + [
                'folders' => $folders->map(function ($folder) use ($counts, $ids) {
                    return ['id' => (string) $folder->id, 'name' => $folder->name,
                        'piece_count' => (int) ($counts[$folder->id] ?? 0), 'has_piece' => in_array((string) $folder->id, $ids, true)];
                })->all(),
                'can_create_folder' => app(FavoriteAccess::class)->canCreate($user, $folders->count()),
            ];
        }, 3);
    }

    public function change(User $user, string $folderId, string $pieceId, bool $save): array
    {
        return DB::transaction(function () use ($user, $folderId, $pieceId, $save) {
            $access = app(FavoriteAccess::class);
            $access->lock($user);
            $folder = $this->ownedFolder($user, $folderId);
            $piece = Piece::findOrFail($pieceId);
            $membership = Favorite::retrieve($user, $piece, $folder);
            if ($save && !$membership->exists()) {
                $this->assertAllowed($access->additionRestriction($user, $folder));
                Favorite::addTo($user, $piece, $folder);
                $folder->sort();
            } elseif (!$save && $membership->exists()) {
                $membership->delete();
                $folder->sort();
            }
            // Already-saved PUT and already-absent DELETE also return current state.
            return $this->state($user, $folder, $pieceId);
        }, 3);
    }

    public function create(User $user, array $input, ?string $key): array
    {
        return DB::transaction(function () use ($user, $input, $key) {
            $access = app(FavoriteAccess::class);
            $access->lock($user);
            $pieceId = $input['piece_id'] ?? null;
            $hash = hash('sha256', json_encode(['name' => $input['name'], 'piece_id' => $pieceId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ($key) {
                $receipts = DB::table('api_v2_favorite_folder_receipts')->where('user_id', $user->id)->where('idempotency_key', $key);
                $receipt = (clone $receipts)->lockForUpdate()->first();
                if ($receipt && carbon($receipt->expires_at)->isFuture()) {
                    if (!hash_equals($receipt->request_hash, $hash)) throw new FavoriteFailure('idempotency_conflict', 'This Idempotency-Key was used with different request data.', 409);
                    $folder = $this->ownedFolder($user, $receipt->folder_id);
                    if ($pieceId !== null) Piece::findOrFail($pieceId);
                    // Replays never re-save a piece removed later on the website.
                    return $this->state($user, $folder, $pieceId);
                }
                if ($receipt) $receipts->delete();
            }
            $piece = $pieceId !== null ? Piece::findOrFail($pieceId) : null;
            Validator::make($input, ['name' => [Rule::unique('favorite_folders')->where('user_id', $user->id)]],
                ['name.unique' => 'You already have a folder with this name.'])->validate();
            $this->assertAllowed($access->creationRestriction($user));
            $colors = FolderPalette::preserveExisting($user->id);
            $folder = FavoriteFolder::create(['user_id' => $user->id, 'name' => $input['name'], 'web_color' => FolderPalette::suggest($colors)]);
            if ($piece) {
                $this->assertAllowed($access->additionRestriction($user, $folder));
                Favorite::addTo($user, $piece, $folder);
            }
            if ($key) DB::table('api_v2_favorite_folder_receipts')->insert([
                'user_id' => $user->id, 'idempotency_key' => $key, 'request_hash' => $hash, 'folder_id' => $folder->id,
                'created_at' => now(), 'expires_at' => now()->addHours(self::RECEIPT_HOURS),
            ]);
            return $this->state($user, $folder, $pieceId);
        }, 3);
    }

    private function ownedFolder(User $user, $id): FavoriteFolder
    {
        return $user->favoriteFolders()->lockForUpdate()->findOrFail($id);
    }

    private function state(User $user, FavoriteFolder $folder, ?string $pieceId): array
    {
        $ids = $pieceId !== null ? $this->folderIds($user, $pieceId) : [];
        return $this->pieceState($pieceId, $ids) + [
            'folder' => ['id' => (string) $folder->id, 'name' => $folder->name,
                'piece_count' => Favorite::where('user_id', $user->id)->where('favorite_folder_id', $folder->id)->count(),
                'has_piece' => $pieceId !== null ? in_array((string) $folder->id, $ids, true) : null],
            'can_create_folder' => app(FavoriteAccess::class)->canCreate($user, $user->favoriteFolders()->count()),
        ];
    }

    private function pieceState(?string $pieceId, array $ids): array
    {
        return ['piece_id' => $pieceId, 'is_favorited' => $pieceId !== null ? count($ids) > 0 : null, 'favorite_folder_ids' => $ids];
    }

    private function assertAllowed(?array $restriction): void
    {
        if ($restriction) throw new FavoriteFailure($restriction['code'], $restriction['message'], 403);
    }
}
