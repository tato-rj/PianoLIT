<?php

namespace App\Http\Controllers\Admin;

use App\{User, Piece, Composer, Subscription, Location};
use App\Billing\Payment;
use App\Infograph\Infograph;
use App\CrashCourse\CrashCourse;
use App\Quiz\{Quiz, Level, QuizResult};
use App\Quiz\Topic as QuizTopic;
use App\Shop\{eBook, eScore};
use App\Blog\Post;
use App\Tools\Stats;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Validator;

class AdminsController extends Controller
{
    /**
     * Display the home page.
     */
    public function home()
    {
        $userStats = [
            'all' => [
                'label' => 'Users', 
                'total' => User::count(),
                'counts' => [
                    User::upUntilLastWeek()->count(), 
                    User::count()], 
                'url' => route('admin.users.index')],
            'platforms' => [
                [
                    'icon' => ['icon' => 'apple', 'fa_type' => 'b'], 
                    'label' => 'iOS', 
                    'total' => User::byOrigin('ios')->count(),
                    'counts' => [
                        User::byOrigin('ios')->upUntilLastWeek()->count(), 
                        User::byOrigin('ios')->count()], 
                    'url' => route('admin.users.logs')
                ], [
                    'icon' => ['icon' => 'bolt'], 
                    'label' => 'WebApp', 
                    'total' => User::byOrigin('webapp')->count(),
                    'counts' => [
                        User::byOrigin('webapp')->upUntilLastWeek()->count(), 
                        User::byOrigin('webapp')->count()], 
                    'url' => route('admin.users.logs')
                ], [
                    'icon' => ['icon' => 'laptop'], 
                    'label' => 'Web', 
                    'total' => User::byOrigin('web')->count(),
                    'counts' => [
                        User::byOrigin('web')->upUntilLastWeek()->count(), 
                        User::byOrigin('web')->count()], 
                    'url' => route('admin.users.logs')
                ]
            ]
        ];

        $counts = [
            [
                'label' => 'Subscribers', 
                'total' => Subscription::count(),
                'counts' => [
                    Subscription::upUntilLastWeek()->count(), 
                    Subscription::count()], 
                'url' => route('admin.subscriptions.index')
            ], [
                'label' => 'Pieces', 
                'total' => Piece::count(),
                'counts' => [
                    Piece::upUntilLastWeek()->count(), 
                    Piece::count()], 
                'url' => route('admin.pieces.index')
            ], [
                'label' => 'Quizzes', 
                'total' => Quiz::published()->count(),
                'counts' => [
                    Quiz::published()->upUntilLastWeek()->count(), 
                    Quiz::published()->count()], 
                'url' => route('admin.quizzes.index')
            ], [
                'label' => 'Blog posts', 
                'total' => Post::published()->count(),
                'counts' => [
                    Post::published()->upUntilLastWeek()->count(), 
                    Post::published()->count()], 
                'url' => route('admin.posts.index')
            ], [
                'label' => 'Infographics', 
                'total' => Infograph::published()->count(),
                'counts' => [
                    Infograph::published()->upUntilLastWeek()->count(), 
                    Infograph::published()->count()], 
                'url' => route('admin.infographs.index')
            ], [
                'label' => 'eBooks', 
                'total' => eBook::published()->count(),
                'counts' => [
                    eBook::published()->upUntilLastWeek()->count(), 
                    eBook::published()->count()], 
                'url' => route('admin.ebooks.index')
            ], [
                'label' => 'eScores', 
                'total' => eScore::published()->count(),
                'counts' => [
                    eScore::published()->upUntilLastWeek()->count(), 
                    eScore::published()->count()], 
                'url' => route('admin.escores.index')
            ], [
                'label' => 'Crash Courses', 
                'total' => CrashCourse::published()->count(),
                'counts' => [
                    CrashCourse::published()->upUntilLastWeek()->count(), 
                    CrashCourse::published()->count()], 
                'url' => route('admin.crashcourses.index')
            ],
        ];

        $birthdays = Composer::bornToday()->get();
        $deathdays = Composer::diedToday()->get();

        $iosUsers = User::byOrigin('ios')->count();
        $iosMembers = User::byOrigin('ios')->has('membership')->count();
        $webappUsers = User::byOrigin('webapp')->count();
        $webappMembers = User::byOrigin('webapp')->has('membership')->count();
        $webappAveragePayment = Payment::sum('amount')/100;

        return view('admin.pages.home.index', compact(['userStats', 'counts', 'birthdays', 'deathdays', 'iosUsers', 'iosMembers', 'webappUsers', 'webappMembers', 'webappAveragePayment']));
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
    public function blog(Request $request)
    {
        $validator = Validator::make($request->all(), ['name' => 'required']);
        // return $validator->messages();
        dd(get_class_methods($validator));

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
