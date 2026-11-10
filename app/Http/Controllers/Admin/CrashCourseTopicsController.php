<?php

namespace App\Http\Controllers\Admin;

use App\CrashCourse\CrashCourseTopic;
use App\Http\Controllers\Controller;
use App\Services\Admin\{CrashCoursesTable, CrashCourseTopics};
use Illuminate\Http\Request;

class CrashCourseTopicsController extends Controller
{
    public function index(Request $request, CrashCoursesTable $table)
    {
        return redirect($table->modalUrl($request));
    }

    public function store(Request $request, CrashCourseTopics $topics)
    {
        return $topics->save($request);
    }

    public function update(Request $request, CrashCourseTopic $topic, CrashCourseTopics $topics)
    {
        return $topics->save($request, $topic);
    }

    public function destroy(Request $request, CrashCourseTopic $topic, CrashCourseTopics $topics)
    {
        return $topics->delete($request, $topic);
    }
}
