<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\CrashCourse\{CrashCourse, CrashCourseLesson};
use App\Mail\CrashCourseEmail;
use App\Services\CrashCourses\EmailDesign;

class CrashCourseLessonsController extends Controller
{
    private function owns(CrashCourse $course, CrashCourseLesson $lesson): void
    {
        abort_unless((int) $lesson->crash_course_id === (int) $course->id, 404);
    }

    private function editor(CrashCourse $crashcourse, ?CrashCourseLesson $lesson = null)
    {
        $designs = app(EmailDesign::class);
        $saved = $lesson ? $designs->saved($lesson) : null;
        $emailDesign = $saved ? json_decode($saved->design, true) : $designs->initial($lesson);
        $emailRevision = $saved->revision ?? 0;
        return view('admin.pages.crashcourses.lessons.editor', compact('crashcourse', 'lesson', 'emailDesign', 'emailRevision'));
    }

    public function create(CrashCourse $crashcourse) { return $this->editor($crashcourse); }

    public function edit(CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        $this->owns($crashcourse, $lesson);
        return $this->editor($crashcourse, $lesson);
    }

    private function input(Request $request): array
    {
        $request->validate(['subject' => 'required|string|max:120']);
        if ($request->has('email_design')) {
            $request->validate(['email_revision' => 'required|integer|min:0']);
            $design = app(EmailDesign::class)->decode($request->input('email_design'));
            return ['design' => $design, 'body' => app(EmailDesign::class)->html($design)];
        }
        $request->validate(['body' => 'required|string|max:60000']);
        return ['design' => null, 'body' => $request->input('body')];
    }

    private function saveDesign(CrashCourseLesson $lesson, array $design, int $revision): void
    {
        $values = ['design' => json_encode($design), 'revision' => $revision, 'updated_at' => now()];
        if ($revision === 1) {
            DB::table('crash_course_email_designs')->insert($values + ['lesson_id' => $lesson->id, 'created_at' => now()]);
        } else {
            DB::table('crash_course_email_designs')->where('lesson_id', $lesson->id)->update($values);
        }
    }

    public function store(Request $request, CrashCourse $crashcourse)
    {
        $input = $this->input($request);
        DB::transaction(function () use ($request, $crashcourse, $input) {
            CrashCourse::whereKey($crashcourse->id)->lockForUpdate()->firstOrFail();
            $lesson = $crashcourse->lessons()->create([
                'subject' => $request->subject, 'body' => $input['body'], 'reading_time' => calculateReadingTime($input['body']),
                'order' => $crashcourse->lessons()->count(),
            ]);
            if ($input['design']) $this->saveDesign($lesson, $input['design'], 1);
        });
        return redirect(route('admin.crashcourses.edit', $crashcourse))->with('status', 'Lesson created.');
    }

    public function update(Request $request, CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        $this->owns($crashcourse, $lesson);
        $input = $this->input($request);
        DB::transaction(function () use ($request, $lesson, $input) {
            $locked = CrashCourseLesson::whereKey($lesson->id)->lockForUpdate()->firstOrFail();
            $saved = app(EmailDesign::class)->saved($locked);
            if (($input['design'] && (int) $request->email_revision !== (int) ($saved->revision ?? 0)) || (!$input['design'] && $saved)) {
                throw ValidationException::withMessages(['email_design' => 'This lesson changed in another editor. Keep a copy of your changes, then reload the lesson before saving.']);
            }
            $locked->update(['subject' => $request->subject, 'body' => $input['body'], 'reading_time' => calculateReadingTime($input['body'])]);
            if ($input['design']) $this->saveDesign($locked, $input['design'], ($saved->revision ?? 0) + 1);
        });
        return back()->with('status', 'Lesson saved.');
    }

    public function duplicate(Request $request, CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        $this->owns($crashcourse, $lesson);
        DB::transaction(function () use ($crashcourse, $lesson) {
            CrashCourse::whereKey($crashcourse->id)->lockForUpdate()->firstOrFail();
            $source = CrashCourseLesson::whereKey($lesson->id)->lockForUpdate()->firstOrFail();
            $copy = $crashcourse->lessons()->create([
                'subject' => mb_substr($source->subject, 0, 113).' - copy', 'body' => $source->body,
                'reading_time' => $source->reading_time, 'order' => $crashcourse->lessons()->count(),
            ]);
            if ($saved = app(EmailDesign::class)->saved($source)) $this->saveDesign($copy, json_decode($saved->design, true), 1);
        });
        return redirect(route('admin.crashcourses.edit', $crashcourse))->with('status', 'Lesson duplicated.');
    }

    public function preview(CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        $this->owns($crashcourse, $lesson);
        return new CrashCourseEmail($lesson);
    }

    public function sendTo(Request $request, CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        $this->owns($crashcourse, $lesson);
        $request->validate(['email' => 'required|email|max:254']);
        \Mail::to($request->email)->send(new CrashCourseEmail($lesson));
        return back()->with('status', 'A preview was sent to '.e($request->email));
    }

    public function reorder(Request $request, CrashCourse $crashcourse)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'required|integer|distinct|min:1']);
        $ids = array_values(array_map('intval', $data['ids']));

        DB::transaction(function () use ($crashcourse, $ids) {
            CrashCourse::whereKey($crashcourse->id)->lockForUpdate()->firstOrFail();
            $lessons = $crashcourse->lessons()->get()->keyBy('id');
            if (count($ids) !== $lessons->count() || array_diff($ids, $lessons->keys()->all())) {
                throw ValidationException::withMessages(['ids' => 'The lesson list changed. Reload the page and try again.']);
            }

            foreach ($ids as $position => $id) {
                $lessons[$id]->update(['order' => $position]);
            }
        });

        return response(200);
    }

    public function destroy(CrashCourse $crashcourse, CrashCourseLesson $lesson)
    {
        $this->owns($crashcourse, $lesson);
        DB::transaction(function () use ($crashcourse, $lesson) {
            CrashCourse::whereKey($crashcourse->id)->lockForUpdate()->firstOrFail();
            $lesson->delete();
            $crashcourse->lessons()->get()->each(function ($lesson, $index) { $lesson->update(['order' => $index]); });
        });
        return redirect(route('admin.crashcourses.edit', $crashcourse))->with('status', 'Lesson deleted.');
    }
}
