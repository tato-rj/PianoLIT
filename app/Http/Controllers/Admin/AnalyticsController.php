<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\Analytics;
use App\Analytics\MembershipReport;
use App\Analytics\PieceReport;
use App\Analytics\FolderReport;
use App\Analytics\ComposerReport;
use App\Blog\{Post, Topic};
use App\Quiz\{Quiz, QuizResult};
use App\Quiz\Topic as QuizTopic;
use App\Quiz\Level as QuizLevel;
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

    public function pieces(Request $request, PieceReport $report)
    {
        if (request()->ajax())
            return (new Analytics)->for('pieces')->query(request('type'), request()->except('type'))->get();

        return view('admin.pages.analytics.pieces.index', ['report' => $report->get($request)]);
    }

    public function favorites(Request $request, FolderReport $report)
    {
        if (request()->ajax())
            return FavoriteFolder::userCreated()->whereNotIn('user_id', \App\Activity\ExcludedAccounts::ids())->datatable();
        
        return view('admin.pages.analytics.favorites.index', $report->get($request));
    }

    public function memberships(Request $request, MembershipReport $report)
    {
        return view('admin.pages.analytics.memberships.index', $report->get($request));
    }

    public function composers(Request $request, ComposerReport $report)
    {
        return view('admin.pages.analytics.composers.index', $report->get($request));
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
