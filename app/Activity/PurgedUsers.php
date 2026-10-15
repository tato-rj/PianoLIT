<?php

namespace App\Activity;

use Illuminate\Support\Facades\DB;

// Analytics suppression only. Never use this legacy account ID for authorization.
class PurgedUsers
{
    public static function contains($id)
    {
        return DB::table('activity_purged_users')->where('user_id', $id)->exists();
    }

    public static function without(array $rows)
    {
        $ids = array_unique(array_column($rows, 'user_id'));
        sort($ids, SORT_NUMERIC);
        $allowed = [];
        foreach ($ids as $id) if (self::lock($id)) $allowed[] = $id;
        return array_values(array_filter($rows, function ($row) use ($allowed) { return in_array($row['user_id'], $allowed); }));
    }

    // Call inside the writer's transaction, before locking visits or inserting.
    // The account lock matches RemoveUser, so an in-flight writer either commits
    // before cleanup or sees the purge marker afterwards. Locking reads also avoid
    // a stale consistent-read snapshot during a long import transaction.
    public static function lock($id)
    {
        if (!$id) return true;
        DB::table('users')->where('id', $id)->lockForUpdate()->first(['id']);
        return !DB::table('activity_purged_users')->where('user_id', $id)->lockForUpdate()->first(['user_id']);
    }
}
