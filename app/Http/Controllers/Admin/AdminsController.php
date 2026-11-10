<?php

namespace App\Http\Controllers\Admin;

use App\{User, Piece, Composer, Subscription, Location, Tutorial};
use App\Billing\Payment;
use App\Infograph\Infograph;
use App\CrashCourse\CrashCourse;
use App\Quiz\{Quiz, Level, QuizResult};
use App\Quiz\Topic as QuizTopic;
use App\Shop\{eBook, eScore};
use App\Blog\Post;
use App\Tools\Analytics;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminsController extends Controller
{
    /**
     * Display the home page.
     */
    public function home(Request $request, \App\Services\Admin\Dashboard $dashboard)
    {
        // \App\Admin::create([
        //     'name' => 'Dot',
        //     'email' => 'dot@email.com',
        //     'password' => \Hash::make(env('DOT_ADMIN_PASSWORD')),
        //     'role' => 'manager'
        // ]);

        // return \App\Admin::latest()->first();

        $filters = $request->validate([
            'days' => 'sometimes|integer|in:3,7,30',
            'platform' => 'sometimes|string|in:all,web,webapp,app',
        ]);
        $admin = auth('admin')->user();
        $data = $admin->isManager()
            ? $dashboard->data((int) ($filters['days'] ?? 7), $filters['platform'] ?? 'all')
            : [];
        if (!$admin->isManager()) $admin->loadCount(['pieces', 'composers']);

        return view('admin.pages.home.index', ['dashboard' => $data]);
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
        if (request()->ajax())
            return Post::datatable();

        return view('admin.pages.blog.index', app(\App\Services\Admin\PostsTable::class)->get($request));
    }

    /**
     * Display the quiz index page.
     */
    public function quiz(Request $request, \App\Services\Admin\QuizzesTable $table)
    {
        if (request()->ajax())
            return Quiz::datatable();

        return view('admin.pages.quizzes.index', $table->get($request));
    }

    /**
     * Display the quiz index page.
     */
    public function quizTopics(Request $request, \App\Services\Admin\QuizzesTable $table)
    {
        return redirect($table->url($request, 'topics'));
    }

    /**
     * Display the subscriptions index page.
     */
    public function subscriptions(Request $request, \App\Services\Admin\SubscriptionsTable $table)
    {
        return view('admin.pages.subscriptions.index', $table->get($request));
    }
}
