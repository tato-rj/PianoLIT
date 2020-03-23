<?php

namespace App\Http\Controllers;

use App\{Admin, User, Piece, Tag, Composer, Subscription, EmailList, Api};
use App\Quiz\{Quiz, Level, QuizResult};
use App\Quiz\Topic as QuizTopic;
use App\Blog\Post;
use App\Tools\Stats;
use Illuminate\Http\Request;

class AdminsController extends Controller
{
    /**
     * Display the home page.
     *
     * @return \Illuminate\Http\Response
     */
    public function home()
    {
        $pieces_count = Piece::count();
        $composers_count = Composer::count();
        $users_count = User::count();
        $subscriptions_count = Subscription::count();
        $quiz_results_count = QuizResult::count();
        $blog_count = Post::count();

        $birthdays = Composer::bornToday()->get();
        $deathdays = Composer::diedToday()->get();

        $stats = (new Stats)->model(Subscription::class);

        return view('admin.pages.home.index', compact('pieces_count', 'quiz_results_count', 'composers_count', 'users_count', 'subscriptions_count', 'blog_count', 'stats', 'birthdays', 'deathdays'));
    }

    public function notifications()
    {
        if (request()->ajax()) {
            return datatable(auth()->user()->notifications())->withDate()->withBlade([
                'action' => view('admin.pages.notifications.actions')
            ])->withClass(function($element) {
                return $element->read_at ? 'opacity-4' : null;
            })->escape(['data.message'])->make();
        }

        return view('admin.pages.notifications.index');
    }

    /**
     * Display the blog page.
     *
     * @return \Illuminate\Http\Response
     */
    public function blog()
    {
        if (request()->ajax())
            return Post::datatable();

        return view('admin.pages.blog.index');
    }

    /**
     * Display the quiz page.
     *
     * @return \Illuminate\Http\Response
     */
    public function quiz()
    {
        if (request()->ajax())
            return Quiz::datatable();

        $levels = Level::all();

        return view('admin.pages.quizzes.index', compact(['levels']));
    }

    public function quizTopics()
    {
        $topics = QuizTopic::all();

        return view('admin.pages.quizzes.topics.index', compact('topics'));
    }

    /**
     * Display the subscriptions page.
     *
     * @return \Illuminate\Http\Response
     */
    public function subscriptions()
    {
        if (request()->ajax())
            return Subscription::datatable();

        return view('admin.pages.subscriptions.index');
    }
}
