<?php

namespace App\Http\Controllers\Admin;

use App\Stats\Stats;
use App\Blog\{Post, Topic};
use App\Quiz\{Quiz, QuizResult};
use App\Quiz\Topic as QuizTopic;
use App\Quiz\Level as QuizLevel;
use App\Billing\Membership;
use App\{User, Piece, Tag, Composer, Country};
use App\Log\Loggers\DailyLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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

        $favorites = Piece::orderBy('favorites_count', 'DESC')->take(10)->get();
        $views = Piece::orderBy('views_count', 'DESC')->take(10)->get();

        return view('admin.pages.stats.pieces.index', compact(['favorites', 'views']));
    }

    public function subscriptions()
    {
        if (request()->ajax())
            return (new Stats)->for('subscriptions')->query(request('type'), request()->except('type'))->get();

        return view('admin.pages.stats.subscriptions.index');
    }

    public function memberships()
    {
        if (request()->ajax())
            return (new Stats)->for('memberships')->query(request('type'), request()->except('type'))->get();

        $trials = Membership::trial()->newest()->get();
        $members = Membership::member()->get()->sortBy('source.renews_at');
        $expired = Membership::expired()->get()->sortByDesc('source.renews_at');

        return view('admin.pages.stats.memberships.index', compact(['trials', 'members', 'expired']));
    }

    public function composers()
    {
        $composersStats = Composer::has('pieces', '>', 3)->select('name')->withCount('pieces')->orderBy('pieces_count', 'DESC')->get();
        $composersWithFewPieces = Composer::has('pieces', '<=', 3)->pluck('name')->toArray();
        $composersCount = Composer::count();
        $upcomingBirthdays = Composer::upcomingBirthdays(30)->get();
        $upcomingDeathdays = Composer::upcomingDeathdays(30)->get();
        $periodsStats = Composer::byPeriod();
        $countriesStats = Country::has('composers')->withCount('composers')->orderBy('composers_count', 'DESC')->get();
        $mapArray = [['Country', 'Composers']];

        foreach($countriesStats as $country) {
            array_push($mapArray, [$country->name, $country->composers_count]);
        }

        $composers = Composer::all();

        return view('admin.pages.stats.composers.index', compact(['composersStats', 'composersCount', 'composersWithFewPieces', 'upcomingBirthdays', 'upcomingDeathdays', 'periodsStats', 'countriesStats', 'composers', 'mapArray']));
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
