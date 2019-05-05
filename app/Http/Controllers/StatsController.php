<?php

namespace App\Http\Controllers;

use App\Blog\{Post, Topic};
use App\{User, Piece, Tag, Composer};
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function users()
    {
    	$usersDaily = User::stats()->daily();
        $usersMonthly = User::stats()->monthly();
        $usersYearly = User::stats()->yearly();
        $users = User::withCount(['favorites', 'views'])->latest()->get();

        return view('admin.pages.stats.users.index', compact(['usersDaily', 'usersMonthly', 'usersYearly', 'users']));
    }

    public function pieces()
    {
        $tagStats = Tag::whereIn('type', ['mood', 'technique'])->withCount('pieces')->orderBy('pieces_count', 'DESC')->get();
        $tagsCount = Tag::count();
        $composersStats = Composer::select('name')->withCount('pieces')->orderBy('pieces_count', 'DESC')->get();
        $composersCount = Composer::count();
        $levelsStats = Tag::levels()->withCount('pieces')->get();
        $periodsStats = Tag::periods()->withCount('pieces')->get();
        $recStats = Piece::byRecordingsAvailable();
        $pieces = Piece::all();

        return view('admin.pages.stats.pieces.index', compact(['tagStats', 'tagsCount', 'composersStats', 'composersCount', 'levelsStats', 'recStats', 'periodsStats', 'pieces']));
    }

    public function blog()
    {
        $topicStats = Topic::withCount('posts')->orderBy('posts_count', 'DESC')->get();
        $topicsCount = Topic::count();
        $posts = Post::orderBy('published_at', 'DESC')->get();

        return view('admin.pages.stats.blog.index', compact(['topicStats', 'topicsCount', 'posts']));
    }
}
