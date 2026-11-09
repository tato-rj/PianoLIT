<?php

namespace App\Services\WebApp;

use App\{Favorite, FavoriteFolder, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FavoriteAccess
{
    public function lock(User $user)
    {
        // Serialize web/v2 additions per owner so concurrent requests share each cap.
        DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
    }

    public function canCreate(User $user, int $count)
    {
        $cap = app(AccessPolicy::class)->cap('folders', $user);
        return $cap === null || $count < $cap;
    }

    public function canAdd(User $user, int $count)
    {
        $cap = app(AccessPolicy::class)->cap('folder_pieces', $user);
        return $cap === null || $count < $cap;
    }

    public function assertCreate(User $user)
    {
        if ($failure = $this->creationRestriction($user)) $this->deny($failure['message'], 'folders');
    }

    public function assertAdd(User $user, ?FavoriteFolder $folder)
    {
        if ($failure = $this->additionRestriction($user, $folder)) $this->deny($failure['message'], $failure['feature']);
    }

    // Transport-independent decisions shared by browser and bearer-authenticated writes.
    public function creationRestriction(User $user): ?array
    {
        if ($this->canCreate($user, $user->favoriteFolders()->count())) return null;
        return ['code' => app(AccessPolicy::class)->cap('folders', $user) === 0 ? 'favorite_folders_blocked' : 'favorite_folder_limit_reached',
            'message' => 'You have reached your favorite-folder limit.', 'feature' => 'folders'];
    }

    public function additionRestriction(User $user, ?FavoriteFolder $folder): ?array
    {
        if ($folder && app(AccessPolicy::class)->cap('folders', $user) === 0) {
            return ['code' => 'favorite_folders_blocked', 'message' => 'Favorite folders are currently locked.', 'feature' => 'folders'];
        }
        $count = Favorite::where('user_id', $user->id)->where('favorite_folder_id', optional($folder)->id)->count();
        if ($this->canAdd($user, $count)) return null;
        return ['code' => app(AccessPolicy::class)->cap('folder_pieces', $user) === 0 ? 'favorite_saves_blocked' : 'favorite_piece_limit_reached',
            'message' => 'You have reached the limit of pieces in this folder.', 'feature' => 'folder_pieces'];
    }

    public function deny(string $message, string $feature = 'folder_pieces')
    {
        if (request()->expectsJson()) {
            abort(response()->json(['message' => $message, 'errors' => ['access' => [$message]], 'access_prompt' => app(AccessPolicy::class)->promptTarget(auth('web')->user(), $feature)], 403));
        }
        throw ValidationException::withMessages(['access' => $message]);
    }
}
