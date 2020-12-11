<?php

namespace App\Http\Controllers\Admin;

use App\{User, Piece, Composer, Subscription};
use App\Billing\Payment;
use App\Infograph\Infograph;
use App\Quiz\{Quiz, Level, QuizResult};
use App\Quiz\Topic as QuizTopic;
use App\Shop\{eBook, eScore};
use App\Blog\Post;
use App\Tools\Stats;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminsController extends Controller
{
    /**
     * Display the home page.
     */
    public function home()
    {
        $counts = collect([
            'Users' => [User::upUntilLastWeek()->count(), User::count()],
            'Subscribers' => [Subscription::upUntilLastWeek()->count(), Subscription::count()],
            'Pieces' => [Piece::upUntilLastWeek()->count(), Piece::count()],
            'Quizzes' => [Quiz::upUntilLastWeek()->count(), Quiz::count()],
            'Blog posts' => [Post::upUntilLastWeek()->count(), Post::count()],
            'Infographics' => [Infograph::upUntilLastWeek()->count(), Infograph::count()],
            'eBooks' => [eBook::upUntilLastWeek()->count(), eBook::count()],
            'eScores' => [eScore::upUntilLastWeek()->count(), eScore::count()]
        ]);

        $birthdays = Composer::bornToday()->get();
        $deathdays = Composer::diedToday()->get();

        $iosUsers = User::byOrigin('ios')->count();
        $iosMembers = User::byOrigin('ios')->has('membership')->count();
        $webappUsers = User::byOrigin('webapp')->count();
        $webappMembers = User::byOrigin('webapp')->has('membership')->count();
        $webappAveragePayment = Payment::sum('amount')/100;

        return view('admin.pages.home.index', compact(['counts', 'birthdays', 'deathdays', 'iosUsers', 'iosMembers', 'webappUsers', 'webappMembers', 'webappAveragePayment']));
    }

    /**
     * Show all admin notifications.
     */
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
     * Display the blog index page.
     */
    public function blog()
    {
        if (request()->ajax())
            return Post::datatable();

        return view('admin.pages.blog.index');
    }

    /**
     * Display the quiz index page.
     */
    public function quiz()
    {
        if (request()->ajax())
            return Quiz::datatable();

        $levels = Level::all();

        return view('admin.pages.quizzes.index', compact(['levels']));
    }

    /**
     * Display the quiz index page.
     */
    public function quizTopics()
    {
        $topics = QuizTopic::all();

        return view('admin.pages.quizzes.topics.index', compact('topics'));
    }

    /**
     * Display the subscriptions index page.
     */
    public function subscriptions()
    {
        if (request()->ajax())
            return Subscription::datatable();

        return view('admin.pages.subscriptions.index');
    }
}
