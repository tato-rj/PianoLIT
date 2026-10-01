<?php

namespace App\Http\Controllers\Admin;

use App\{Piece, Tutorial, VideoMoment};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VideoMomentsController extends Controller
{
    protected function authorizeVideo(Piece $piece, Tutorial $tutorial)
    {
        abort_unless($tutorial->piece_id == $piece->id, 404);
        $this->authorize('update', $piece);
    }

    protected function revision($moments)
    {
        return hash('sha256', $moments->toJson());
    }

    public function edit(Piece $piece, Tutorial $tutorial)
    {
        $this->authorizeVideo($piece, $tutorial);
        $moments = $tutorial->moments;
        $revision = $this->revision($moments);
        $moments = $moments->sortBy('start_time')->values();
        $tutorial->setRelation('moments', $moments);

        return view('admin.pages.pieces.videos.moments', compact('piece', 'tutorial', 'moments', 'revision'));
    }

    public function update(Request $request, Piece $piece, Tutorial $tutorial)
    {
        $this->authorizeVideo($piece, $tutorial);
        $data = $request->validate([
            'revision' => 'required|string|size:64',
            'moments' => 'nullable|array|max:200',
            'moments.*' => 'required|array',
            'moments.*.id' => 'nullable|integer|distinct',
            'moments.*.start_time' => 'required|string|max:30',
            'moments.*.end_time' => 'nullable|string|max:30',
            'moments.*.title' => 'required|string|max:255',
            'moments.*.comment' => 'required|string|max:2000',
        ], [], [
            'moments' => 'sections',
            'moments.*' => 'section',
            'moments.*.id' => 'section ID',
            'moments.*.start_time' => 'section start time',
            'moments.*.end_time' => 'section end time',
            'moments.*.title' => 'section title',
            'moments.*.comment' => 'section commentary',
        ]);
        $rows = [];
        foreach ($data['moments'] ?? [] as $index => $row) {
            $start = VideoMoment::parseTime($row['start_time']);
            $endInput = $row['end_time'] ?? null;
            $end = $endInput === null || $endInput === '' ? null : VideoMoment::parseTime($endInput);
            $errors = [];
            if ($start === null || $start > 9999999.999) $errors["moments.$index.start_time"] = 'Enter seconds, M:SS or H:MM:SS, with up to three decimal places.';
            if ($endInput !== null && $endInput !== '' && ($end === null || $end > 9999999.999 || $end < $start)) {
                $errors["moments.$index.end_time"] = 'Enter a valid end time at or after the start time.';
            }
            if ($errors) throw ValidationException::withMessages($errors);
            $rows[] = ['id' => $row['id'] ?? null, 'start_time' => $start, 'end_time' => $end,
                'title' => $row['title'], 'comment' => $row['comment'], 'sort_order' => count($rows)];
        }

        usort($rows, function ($left, $right) {
            return ($left['start_time'] <=> $right['start_time']) ?: ($left['sort_order'] <=> $right['sort_order']);
        });
        foreach ($rows as $index => &$row) $row['sort_order'] = $index;
        unset($row);

        DB::transaction(function () use ($tutorial, $data, $rows) {
            // Serialize saves for this video and reject a stale tab's changes.
            Tutorial::whereKey($tutorial->id)->lockForUpdate()->firstOrFail();
            $existing = $tutorial->moments()->get();
            if (!hash_equals($this->revision($existing), $data['revision'])) {
                throw ValidationException::withMessages(['revision' => 'These sections changed in another tab. Reload this page before saving again.']);
            }
            foreach ($rows as $row) {
                if ($row['id'] && !$existing->contains('id', $row['id'])) {
                    throw ValidationException::withMessages(['moments' => 'A section does not belong to this video.']);
                }
            }
            $kept = [];
            foreach ($rows as $row) {
                $id = $row['id'];
                unset($row['id']);
                $moment = $id ? $existing->firstWhere('id', $id) : $tutorial->moments()->make();
                $moment->fill($row)->save();
                $kept[] = $moment->id;
            }
            $tutorial->moments()->whereNotIn('id', $kept)->delete();
        });

        return back()->with('status', 'The video sections have been saved.');
    }
}
