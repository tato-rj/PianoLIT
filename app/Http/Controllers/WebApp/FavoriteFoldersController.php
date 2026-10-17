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
use App\Support\FolderPalette;
use Illuminate\Support\Facades\DB;

class FavoriteFoldersController extends Controller
{
    public function reorder(Request $request, FavoriteFolder $folder)
    {
        abort_unless($folder->user_id == auth()->id(), 403);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('favorites', 'id')->where('favorite_folder_id', $folder->id)],
        ]);

        if (app(\App\Services\WebApp\AccessPolicy::class)->cap('folders', auth('web')->user()) === 0 || app(\App\Services\WebApp\AccessPolicy::class)->cap('folder_pieces', auth('web')->user()) === 0) app(\App\Services\WebApp\FavoriteAccess::class)->deny('Favorites are currently locked.');
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
        abort_unless(app(\App\Services\WebApp\AccessPolicy::class)->available('escores', $user), 403, 'Go Premium to create eScores.');

        $policy = app(\App\Services\WebApp\AccessPolicy::class);
        abort_if($policy->cap('folders', $user) === 0 || $policy->cap('folder_pieces', $user) === 0, 403, 'Favorites are currently locked.');
        $options = \App\PDF\EscoreOptions::validate($request);
        $options['comment'] = $options['comment'] ?? ($folder->description ?: 'for piano');
        $pieces = $folder->favorites->pluck('piece')->filter(function ($piece) {
            return $piece && $piece->score_path && $piece->is_public_domain && $piece->hasWebMediaAccess(auth('web')->user(), 'exports');
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
        $piece = Piece::find($form->piece_id);
        $folder = DB::transaction(function () use ($form, $piece) {
            $access = app(\App\Services\WebApp\FavoriteAccess::class);
            $access->lock(auth('web')->user());
            $access->assertCreate(auth('web')->user());
            $colors = FolderPalette::preserveExisting(auth()->id());

            $folder = FavoriteFolder::create([
                'user_id' => auth()->id(),
                'name' => $form->name,
                'web_color' => $form->color ?? FolderPalette::suggest($colors),
            ]);
            if ($piece) {
                $access->assertAdd(auth('web')->user(), $folder);
                Favorite::toggle(auth('web')->user(), $piece, $folder);
            }
            return $folder;
        });
        \App\Activity\Actions::record($request, 'folder_created', [], 'folder', $folder->id);

        if ($piece) \App\Activity\Actions::record($request, 'favorite_changed', ['result' => true, 'folder_id' => $folder->id], 'piece', $piece->id);

        $folders = auth()->user()->favoriteFolders()->withCount(['favorites as piece_favorites_count' => function ($query) use ($piece) {
            $query->where('piece_id', optional($piece)->id);
        }])->lastUpdated()->get();

        if ($request->wantsJson())
        	return response()->json([
                'favorite' => $piece ? [
                    'piece_id' => $piece->id,
                    'is_favorited' => $piece->favorites()->where('user_id', auth('web')->id())->exists(),
                ] : null,
                'html' => [
                    'list' => $piece ? view('webapp.piece.components.saveto.content', compact(['piece', 'folders']))->render() : null,
                    'flex' => null
                ]
            ]);

        return back()->with(['status' => 'You have a new folder!']);
    }

    public function update(Request $request, FavoriteFoldersForm $form, FavoriteFolder $folder)
    {
        if (app(\App\Services\WebApp\AccessPolicy::class)->cap('folders', auth('web')->user()) === 0) app(\App\Services\WebApp\FavoriteAccess::class)->deny('Favorite folders are currently locked.', 'folders');
        $renamed = $folder->name !== $request->name;
        $attributes = [
    		'name' => $request->name,
    		'description' => $request->description
        ];
        if ($form->has('color')) $attributes['web_color'] = $form->color;
        DB::transaction(function () use ($folder, $attributes) {
            FolderPalette::preserveExisting(auth()->id());
            $folder->update($attributes);
        });
        \App\Activity\Actions::record($request, $renamed ? 'folder_renamed' : 'folder_updated', [], 'folder', $folder->id);

        return back()->with(['status' => 'The folder has been updated.']);
    }

    public function destroy(Request $request, FavoriteFolder $folder)
    {
        if (! auth()->user()->favoriteFolders()->whereKey($folder->id)->exists())
            throw ValidationException::withMessages(['folder' => 'You must own this folder to make changes to it.']);

    	$folder->delete();
        \App\Activity\Actions::record($request, 'folder_deleted', [], 'folder', $folder->id);

    	return back()->with(['status' => 'The folder has been removed.']);
    }
}
