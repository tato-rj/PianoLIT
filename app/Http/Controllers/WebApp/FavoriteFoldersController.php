<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{FavoriteFolder, Piece, Favorite, User};
use App\Http\Requests\FavoriteFoldersForm;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\PDF\PDFGenerator;
use App\Events\eScoreGenerated;

class FavoriteFoldersController extends Controller
{
    public function reorder(Request $request, FavoriteFolder $folder)
    {
        abort_unless($folder->user_id == auth()->id(), 403);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('favorites', 'id')->where('favorite_folder_id', $folder->id)],
        ]);

        $folder->sort($request->ids);

        return view('components.alert', [
            'color' => 'green',
            'message' => '<i class="fas fa-check-circle mr-2"></i>The order has been updated',
            'temporary' => true,
            'dismissible' => true,
            'floating' => 'top',
        ])->render();
    }

    public function pdf(Request $request, FavoriteFolder $folder)
    {
        abort_unless($folder->user_id == auth()->id(), 403);
        $user = auth('web')->user();
        abort_unless($user && $user->hasActiveSubscription(), 403, 'Go Premium to create eScores.');

        $options = \App\PDF\EscoreOptions::validate($request);
        $options['comment'] = $options['comment'] ?? ($folder->description ?: 'for piano');
        $pieces = $folder->favorites->pluck('piece')->filter(function ($piece) {
            return $piece && $piece->score_path && $piece->is_public_domain && $piece->hasWebMediaAccess(auth('web')->user());
        });
        $pieces = \App\PDF\EscoreOptions::selectPieces($pieces, $options);
        abort_if($pieces->isEmpty(), 403, 'No eligible scores are available in this folder.');
        $options['creator'] = auth('web')->user()->full_name;

        try {
            $generator = app(PDFGenerator::class)->pieces($pieces)
                ->request($options, true, true);
            $pdf = $generator->generate();

            if ($request->boolean('preview')) {
                return response()->json(array_merge($generator->metadata(), ['pdf' => base64_encode($pdf->output())]))->header('Cache-Control', 'private, no-store');
            }

            event(new eScoreGenerated(auth()->user(), $folder));
        } catch (\Exception $e) {
            if ($request->boolean('preview')) {
                report($e);
                return response()->json(['message' => 'One of these scores could not be processed. Adjust your selection or try again.'], 422);
            }
            bugreport($e);

            return back()->with('error', 'Sorry, one of the scores in this folder cannot be processed. This error has been reported and we will fix this issue soon!');
        }

        return $request->isMethod('post') ? $pdf->download() : $pdf->stream();
    }

    public function store(Request $request, FavoriteFoldersForm $form)
    {
        $folder = FavoriteFolder::create([
            'user_id' => auth()->user()->id,
            'name' => $form->name
        ]);

        if ($piece = Piece::find($form->piece_id)) {
            Favorite::toggle(
                auth()->user(), 
                $piece, 
                $folder
            );
        }

        $folders = auth()->user()->favoriteFolders()->withCount(['favorites as piece_favorites_count' => function ($query) use ($piece) {
            $query->where('piece_id', optional($piece)->id);
        }])->lastUpdated()->get();

        if ($request->wantsJson())
        	return response()->json([
                'html' => [
                    'list' => $piece ? view('webapp.piece.components.saveto.content', compact(['piece', 'folders']))->render() : null,
                    'flex' => null
                ]
            ]);

        return back()->with(['status' => 'You have a new folder!']);
    }

    public function update(Request $request, FavoriteFoldersForm $form, FavoriteFolder $folder)
    {
    	$folder->update([
    		'name' => $request->name,
    		'description' => $request->description
    	]);

        return back()->with(['status' => 'The folder has been updated.']);
    }

    public function destroy(Request $request, FavoriteFolder $folder)
    {
        if (! auth()->user()->favoriteFolders()->whereKey($folder->id)->exists())
            throw ValidationException::withMessages(['folder' => 'You must own this folder to make changes to it.']);

    	$folder->delete();

    	return back()->with(['status' => 'The folder has been removed.']);
    }
}
