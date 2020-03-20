<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrashCourse\{CrashCourse, CrashCourseSubscription};

class CrashCourseSubscriptionsController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return CrashCourseSubscription::datatable();

    	return view('admin.pages.crashcourses.subscriptions.index');
    }

    public function next(CrashCourseSubscription $subscription)
    {
        $subscription->continue();

        return redirect()->back()->with('status', 'The next lesson has been sent to ' . $subscription->email . '.');
    }

    public function resend(CrashCourseSubscription $subscription)
    {
        $subscription->resend();

        return redirect()->back()->with('status', 'The current lesson has been resent to ' . $subscription->email . '.');
    }

    public function cancel(CrashCourseSubscription $subscription)
    {
        $subscription->cancel();

        return redirect()->back()->with('status', $subscription->first_name . ' will stop receiving emails.');
    }
}
