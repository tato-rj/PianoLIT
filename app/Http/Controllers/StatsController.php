<?php

namespace App\Http\Controllers;

use App\Blog\{Post, Topic};
use App\Quiz\Quiz;
use App\Quiz\Topic as QuizTopic;
use App\Quiz\Level as QuizLevel;
use App\{User, Piece, Tag, Composer, Country};
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
        
        $levelsStats = Tag::levels()->withCount('pieces')->get();
        $periodsStats = Tag::periods()->withCount('pieces')->get();
        $recStats = Piece::byRecordingsAvailable();
        
        $pieces = Piece::withCount('tags')->get();
        $tagsPiecesStats = $pieces->groupBy('tags_count');

        $publicDomainCount = Piece::inPublicDomain()->count();
        $youtubeCount = Piece::withYoutube()->count();
        $itunesCount = Piece::withiTunes()->count();

        return view('admin.pages.stats.pieces.index', compact([
            'tagStats', 'tagsCount', 'levelsStats', 'recStats', 'periodsStats', 'pieces', 
            'youtubeCount', 'itunesCount', 'tagsPiecesStats', 'publicDomainCount'
        ]));
    }

    public function composers()
    {
        $composersStats = Composer::has('pieces', '>', 3)->select('name')->withCount('pieces')->orderBy('pieces_count', 'DESC')->get();
        $composersWithFewPieces = Composer::has('pieces', '<=', 3)->pluck('name')->toArray();
        $composersCount = Composer::count();
        $upcomingBirthdays = Composer::upcomingBirthdays(30)->get();
        $upcomingDeathdays = Composer::upcomingDeathdays(30)->get();
        $periodsStats = Composer::byPeriod();
        $countriesStats = Country::withCount('composers')->orderBy('composers_count', 'DESC')->get();
        $composers = Composer::all();

        return view('admin.pages.stats.composers.index', compact(['composersStats', 'composersCount', 'composersWithFewPieces', 'upcomingBirthdays', 'upcomingDeathdays', 'periodsStats', 'countriesStats', 'composers']));
    }

    public function blog()
    {
        $topicStats = Topic::withCount('posts')->orderBy('posts_count', 'DESC')->get();
        $topicsCount = Topic::count();
        $posts = Post::orderBy('published_at', 'DESC')->get();

        return view('admin.pages.stats.blog.index', compact(['topicStats', 'topicsCount', 'posts']));
    }

    public function quizzes()
    {
        $topicStats = QuizTopic::withCount('quizzes')->orderBy('quizzes_count', 'DESC')->get();
        $levelStats = QuizLevel::withCount('quizzes')->orderBy('quizzes_count', 'DESC')->get();
        $topicsCount = QuizTopic::count();
        $quizzes = Quiz::orderBy('published_at', 'DESC')->get();

        return view('admin.pages.stats.quizzes.index', compact(['levelStats', 'topicStats', 'topicsCount', 'quizzes']));
    }
}
