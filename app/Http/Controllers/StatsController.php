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

        $usersAge = User::stats()->age();
        $usersOccupation = User::stats()->occupation();
        $usersExperience = User::stats()->experience();
        $users = User::withCount(['favorites', 'views'])->latest()->get();

        return view('admin.pages.stats.users.index', compact(['usersDaily', 'usersMonthly', 'usersYearly', 'usersAge', 'usersOccupation', 'usersExperience', 'users']));
    }

    public function pieces()
    {
        $tagStats = Tag::whereIn('type', ['mood', 'technique'])->withCount('pieces')->orderBy('pieces_count', 'DESC')->get();
        $tagsCount = Tag::count();

        $composersStats = Composer::has('pieces', '>', 3)->select('name')->withCount('pieces')->orderBy('pieces_count', 'DESC')->get();
        $composersWithFewPieces = Composer::has('pieces', '<=', 3)->pluck('name')->toArray();
        $composersCount = Composer::count();
        
        $levelsStats = Tag::levels()->withCount('pieces')->get();
        $periodsStats = Tag::periods()->withCount('pieces')->get();
        $recStats = Piece::byRecordingsAvailable();
        
        $pieces = Piece::withCount('tags')->get();
        $tagsPiecesStats = $pieces->groupBy('tags_count');

        $publicDomainCount = Piece::inPublicDomain()->count();
        $youtubeCount = Piece::withYoutube()->count();
        $itunesCount = Piece::withiTunes()->count();

        return view('admin.pages.stats.pieces.index', compact([
            'tagStats', 'tagsCount', 'composersStats', 'composersCount', 'composersWithFewPieces',
            'levelsStats', 'recStats', 'periodsStats', 'pieces', 
            'youtubeCount', 'itunesCount', 'tagsPiecesStats', 'publicDomainCount'
        ]));
    }

    public function blog()
    {
        $topicStats = Topic::withCount('posts')->orderBy('posts_count', 'DESC')->get();
        $topicsCount = Topic::count();
        $posts = Post::orderBy('published_at', 'DESC')->get();

        return view('admin.pages.stats.blog.index', compact(['topicStats', 'topicsCount', 'posts']));
    }
}
