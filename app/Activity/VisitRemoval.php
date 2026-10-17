<?php

namespace App\Activity;

use Illuminate\Support\Facades\DB;

class VisitRemoval
{
    public function delete($id)
    {
        DB::transaction(function () use ($id) {
            // Serialize with browser event writers and other visit cleanup.
            abort_unless(DB::table('activity_visits')->where('id', $id)->lockForUpdate()->first(['id']), 404);
            abort_unless((new Queries)->visits()->where('v.id', $id)->exists(), 404);

            // Some historical events have only a page association.
            DB::table('activity_events')->where(function ($query) use ($id) {
                $query->where('visit_id', $id)->orWhereIn('page_id', DB::table('activity_pages')->where('visit_id', $id)->select('id'));
            })->delete();
            DB::table('activity_pages')->where('visit_id', $id)->delete();
            DB::table('activity_visit_links')->where('visit_id', $id)->delete();
            DB::table('activity_visits')->where('id', $id)->delete();
        }, 3);
    }
}
