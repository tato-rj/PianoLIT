<?php

namespace App\Http\Controllers\WebApp;

use App\Piece;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScoreAnnotationsController extends Controller
{
    protected function identity(Request $request, Piece $piece)
    {
        abort_unless(auth('web')->user()->hasActiveSubscription(), 403);
        if (! $piece->score_path || ! $piece->isPublicDomain) {
            abort(response()->json(['message' => 'No annotatable score is available for this piece.'], 404));
        }
        abort_if(strlen($request->getContent()) > 2000000, 413);
        $data = $request->validate([
            'score' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'fingerprint' => ['required', 'string', 'regex:/\A[a-f0-9]{32,128}\z/'],
        ]);
        abort_unless(hash_equals(hash('sha256', $piece->score_path), $data['score']), 409, 'The score has changed. Reload this page.');

        return [
            'user_id' => auth('web')->id(),
            'piece_id' => $piece->id,
            'score_key' => hash('sha256', $data['score'].':'.$data['fingerprint']),
        ];
    }

    public function show(Request $request, Piece $piece)
    {
        $record = DB::table('score_annotations')->where($this->identity($request, $piece))->first();

        return response()->json([
            'revision' => $record ? (int) $record->revision : 0,
            'marks' => $record ? json_decode($record->marks, true) : [],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, Piece $piece)
    {
        $identity = $this->identity($request, $piece);
        $data = $request->validate([
            'revision' => 'required|integer|min:0|max:4294967294',
            'marks' => 'present|array|max:1000',
            'marks.*' => 'required|array:id,type,page,color,width,points,size,x,y,text',
            'marks.*.id' => ['required', 'string', 'distinct', 'regex:/\A[a-zA-Z0-9_-]{1,64}\z/'],
            'marks.*.type' => ['required', Rule::in(['stroke', 'highlight', 'text'])],
            'marks.*.page' => 'required|integer|min:1|max:2000',
            'marks.*.color' => ['required', 'string', 'regex:/\A#[a-fA-F0-9]{6}\z/'],
            'marks.*.width' => 'required_if:marks.*.type,stroke,highlight|numeric|between:0.001,0.02',
            'marks.*.points' => 'required_if:marks.*.type,stroke,highlight|array|min:1|max:1500',
            'marks.*.points.*' => 'required|array:x,y',
            'marks.*.points.*.x' => 'required|numeric|between:0,1',
            'marks.*.points.*.y' => 'required|numeric|between:0,1',
            'marks.*.size' => 'required_if:marks.*.type,text|numeric|between:0.01,0.08',
            'marks.*.x' => 'required_if:marks.*.type,text|numeric|between:0,1',
            'marks.*.y' => 'required_if:marks.*.type,text|numeric|between:0,1',
            'marks.*.text' => 'required_if:marks.*.type,text|string|max:80',
        ]);
        // Save only validated fields; identity always comes from the web session and route.
        $marks = array_values($data['marks']);
        $revision = (int) $data['revision'];
        $result = DB::transaction(function () use ($identity, $marks, $revision) {
            if ($revision === 0) {
                DB::table('score_annotations')->insertOrIgnore(array_merge($identity, [
                    'revision' => 0, 'marks' => '[]', 'created_at' => now(), 'updated_at' => now(),
                ]));
            }
            $updated = DB::table('score_annotations')->where($identity)->where('revision', $revision)->update([
                'marks' => json_encode($marks), 'revision' => $revision + 1, 'updated_at' => now(),
            ]);
            if ($updated) return ['revision' => $revision + 1];

            $record = DB::table('score_annotations')->where($identity)->first();
            // Retrying a save whose response was lost must not duplicate it or report a conflict.
            if ($record && (int) $record->revision === $revision + 1 && json_decode($record->marks, true) == $marks) {
                return ['revision' => (int) $record->revision];
            }
            abort(409, 'Annotations changed in another tab or device. Reload saved markings before editing.');
        });

        return response()->json($result)->header('Cache-Control', 'private, no-store');
    }
}
