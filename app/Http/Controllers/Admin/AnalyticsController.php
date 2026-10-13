<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\Analytics;
use App\Blog\{Post, Topic};
use App\Quiz\{Quiz, QuizResult};
use App\Quiz\Topic as QuizTopic;
use App\Quiz\Level as QuizLevel;
use App\Billing\Membership;
use App\{User, Piece, Tag, Composer, Country, FavoriteFolder};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AnalyticsController extends Controller
{
    public function users()
    {
        if (request()->ajax())
            return (new Analytics)->for('users')->query(request('type'), request()->except('type'))->get();

        return view('admin.pages.analytics.users.index');
    }

    public function logs()
    {
        if (request()->ajax())
            return (new Analytics)->for('logs')->query(request('type'), request()->except('type'))->get();

        return view('admin.pages.analytics.logs.index');
    }

    public function pieces()
    {
        if (request()->ajax())
            return (new Analytics)->for('pieces')->query(request('type'), request()->except('type'))->get();

        $favorites = Piece::orderBy('favorites_count', 'DESC')->take(10)->get();
        $views = Piece::orderBy('views_count', 'DESC')->take(10)->get();

        return view('admin.pages.analytics.pieces.index', compact(['favorites', 'views']));
    }

    public function favorites()
    {
        if (request()->ajax())
            return FavoriteFolder::userCreated()->datatable();
        
        return view('admin.pages.analytics.favorites.index');
    }

    public function subscriptions()
    {
        if (request()->ajax())
            return (new Analytics)->for('subscriptions')->query(request('type'), request()->except('type'))->get();

        return view('admin.pages.analytics.subscriptions.index');
    }

    public function memberships()
    {
        if (request()->ajax())
            return (new Analytics)->for('memberships')->query(request('type'), request()->except('type'))->get();

        $trials = Membership::trial()->newest()->get();
        $members = Membership::member()->get()->sortBy('source.renews_at');
        $expired = Membership::expired()->get()->sortByDesc('source.renews_at');

        return view('admin.pages.analytics.memberships.index', compact(['trials', 'members', 'expired']));
    }

    public function composers()
    {
        $composersAnalytics = Composer::has('pieces', '>', 3)->select('name')->withCount('pieces')->orderBy('pieces_count', 'DESC')->get();
        $composersWithFewPieces = Composer::has('pieces', '<=', 3)->pluck('name')->toArray();
        $composersCount = Composer::count();
        $upcomingBirthdays = Composer::upcomingBirthdays(30)->get();
        $upcomingDeathdays = Composer::upcomingDeathdays(30)->get();
        $periodsAnalytics = Composer::byPeriod();
        $countriesAnalytics = Country::has('composers')->withCount('composers')->orderBy('composers_count', 'DESC')->get();
        $countriesArray = [['Country', 'Composers']];

        foreach($countriesAnalytics as $country) {
            array_push($countriesArray, [$country->name, $country->composers_count]);
        }

        $composers = Composer::all();

        return view('admin.pages.analytics.composers.index', compact(['composersAnalytics', 'composersCount', 'composersWithFewPieces', 'upcomingBirthdays', 'upcomingDeathdays', 'periodsAnalytics', 'countriesAnalytics', 'composers', 'countriesArray']));
    }

    public function blog()
    {
        $topicAnalytics = Topic::withCount('posts')->orderBy('posts_count', 'DESC')->get();
        $topicsCount = Topic::count();
        $posts = Post::orderBy('published_at', 'DESC')->get();

        return view('admin.pages.analytics.blog.index', compact(['topicAnalytics', 'topicsCount', 'posts']));
    }

    public function quizzes()
    {
        $topicAnalytics = QuizTopic::withCount('quizzes')->orderBy('quizzes_count', 'DESC')->get();
        $levelAnalytics = QuizLevel::withCount('quizzes')->orderBy('quizzes_count', 'DESC')->get();
        $topicsCount = QuizTopic::count();
        $quizzes = Quiz::orderBy('published_at', 'DESC')->get();
        $results_graph = QuizResult::analytics(15);

        return view('admin.pages.analytics.quizzes.index', compact(['levelAnalytics', 'topicAnalytics', 'topicsCount', 'quizzes', 'results_graph']));
    }

    public function infographs()
    {
        
    }
}
