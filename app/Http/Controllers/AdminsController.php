<?php

namespace App\Http\Controllers;

use App\{Admin, User, Piece, Tag, Composer, Subscription, Api};
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
        // return \Auth::logout();
        $pieces_count = Piece::count();
        $composers_count = Composer::count();
        $users_count = User::count();
        $subscriptions_count = Subscription::activeList('newsletter_list')->count() - 2;
        $quiz_results_count = QuizResult::count();
        $blog_count = Post::count();

        $birthdays = Composer::bornToday()->get();
        $deathdays = Composer::diedToday()->get();

        $stats = (new Stats)->model(Subscription::class);

        return view('admin.pages.home.index', compact('pieces_count', 'quiz_results_count', 'composers_count', 'users_count', 'subscriptions_count', 'blog_count', 'stats', 'birthdays', 'deathdays'));
    }

    public function notifications()
    {
        return view('admin.pages.notifications.index');
    }

    /**
     * Display the blog page.
     *
     * @return \Illuminate\Http\Response
     */
    public function blog()
    {
        $posts = Post::latest()->get();

        return view('admin.pages.blog.index', compact('posts'));
    }

    /**
     * Display the quiz page.
     *
     * @return \Illuminate\Http\Response
     */
    public function quiz()
    {
        $quizzes = Quiz::latest()->get();
        $levels = Level::all();

        return view('admin.pages.quizzes.index', compact(['quizzes', 'levels']));
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
        $subscriptions = Subscription::latest()->get();

        return view('admin.pages.subscriptions.index', compact('subscriptions'));
    }
}
