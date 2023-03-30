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

        $isActive = $user->getStatus() == 'active';
        $isTrial = $user->getStatus() == 'trial';
        $isFan = $user->logs_count >= $min;
        $askedRecently = $user->ratings()->unconfirmed()->recently()->exists();
        $hasReview = $user->ratings()->confirmed()->exists();

        if (! $request->has('example'))
            $user->ratings()->firstOrCreate([]);

        return [
            'shouldReview' => ! $isTrial && ! $askedRecently && ! $hasReview && ($isActive || $isFan)
        ];
    }
}
