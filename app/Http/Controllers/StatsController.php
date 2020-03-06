<?php

namespace App\Http\Controllers;

use App\Stats\Stats;
use App\Blog\{Post, Topic};
use App\Quiz\{Quiz, QuizResult};
use App\Quiz\Topic as QuizTopic;
use App\Quiz\Level as QuizLevel;
use App\{User, Piece, Tag, Composer, Country};
use Illuminate\Http\Request;
use App\Log\Loggers\DailyLog;

class StatsController extends Controller
{
    public function users()
    {
        if (request()->ajax())
            return (new Stats)->for('users')->query(request('type'), request()->except('type'))->get();

        return view('admin.pages.stats.users.index');
    }

    public function pieces()
    {
        if (request()->ajax())
            return (new Stats)->for('pieces')->query(request('type'), request()->except('type'))->get();

        return view('admin.pages.stats.pieces.index', compact([]));
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
        $results_graph = QuizResult::stats(15);

        return view('admin.pages.stats.quizzes.index', compact(['levelStats', 'topicStats', 'topicsCount', 'quizzes', 'results_graph']));
    }

    public function infographs()
    {
        
    }
}
