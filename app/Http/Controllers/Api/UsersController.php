<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class UsersController extends Controller
{
    public function shouldReview(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $min = testing() ? 1 : 100;

        $status = cache()->remember('user.'.$user->id.'.status', now()->addDay(), function() use ($user) {
            return $user->getStatus();
        });

        $isActive = $status == 'active';
        $isTrial = $status == 'trial';
        $isFan = $user->logs_count >= $min;
        $askedRecently = $user->ratings()->unconfirmed()->recently()->exists();
        $hasReview = $user->ratings()->confirmed()->exists();
        $tooManyAttempts = $user->ratings()->unconfirmed()->tooMany()->exists();

        $shouldReview = ! $tooManyAttempts && ! $isTrial && ! $askedRecently && ! $hasReview && ($isActive || $isFan);

        if (! $request->has('example')) {
            $user->ratings()->firstOrCreate([]);
            
            if ($shouldReview)
                $user->ratings()->first()->increment('attempts');
        }

        return [
            'should_review' => $shouldReview
        ];
    }

    public function saveReview(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'score' => 'required'
        ]);

        User::find($request->user_id)->ratings()->unconfirmed()->first()->update(['score' => $request->score]);

        return response(200);
    }
}
