<?php

namespace App\Services\WebApp;

use App\{Favorite, FavoriteFolder, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FavoriteAccess
{
    public function lock(User $user)
    {
        // Serialize web additions per owner so concurrent requests share each cap.
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
        if (!$this->canCreate($user, $user->favoriteFolders()->count())) $this->deny('You have reached your favorite-folder limit.', 'folders');
    }

    public function assertAdd(User $user, ?FavoriteFolder $folder)
    {
        if ($folder && app(AccessPolicy::class)->cap('folders', $user) === 0) $this->deny('Favorite folders are currently locked.', 'folders');
        $count = Favorite::where('user_id', $user->id)->where('favorite_folder_id', optional($folder)->id)->count();
        if (!$this->canAdd($user, $count)) $this->deny('You have reached the limit of pieces in this folder.');
    }

    public function deny(string $message, string $feature = 'folder_pieces')
    {
        if (request()->expectsJson()) {
            abort(response()->json(['message' => $message, 'errors' => ['access' => [$message]], 'access_prompt' => app(AccessPolicy::class)->promptTarget(auth('web')->user(), $feature)], 403));
        }
        throw ValidationException::withMessages(['access' => $message]);
    }
}
