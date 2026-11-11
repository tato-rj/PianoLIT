<?php

namespace App\Http\Controllers\Admin;

use App\CrashCourse\{CrashCourse, CrashCourseLesson};
use App\Http\Controllers\Controller;
use App\Mail\CrashCourseEmail;
use App\Services\CrashCourses\EmailDesign;
use App\Support\EmailLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};

class CrashCourseEmailBuilderController extends Controller
{
    public function preview(Request $request, CrashCourse $crashcourse, EmailDesign $designs)
    {
        $request->validate(['subject' => 'nullable|string|max:120']);
        $design = $designs->decode($request->input('email_design'));
        $lesson = new CrashCourseLesson(['subject' => $request->input('subject') ?: 'Lesson preview', 'body' => '']);
        $mail = new CrashCourseEmail($lesson);
        $mail->emailDesign = $design;
        $mail->builderPreview = true;
        $mail->build();
        $renderer = clone app(\Illuminate\Mail\Markdown::class);
        $renderer->theme($mail->theme);
        return response()->json(['html' => (string) $renderer->render($mail->markdown, $mail->buildViewData())])->header('Cache-Control', 'no-store');
    }

    public function upload(Request $request, CrashCourse $crashcourse)
    {
        $request->validate(['file' => 'required|file|image|mimes:jpg,jpeg,png,gif|max:4096|dimensions:max_width=4000,max_height=4000']);
        $path = $request->file('file')->store('crashcourses/emails/'.$crashcourse->id, 'public');
        abort_unless($path, 500);
        // An absolute main-site URL works in inboxes and from the admin subdomain.
        $url = Storage::disk('public')->url($path);
        if (strpos($url, '/') === 0) $url = EmailLayout::websiteUrl().$url;
        if (!preg_match('#^https?://#', $url)) $url = EmailLayout::websiteUrl().'/storage/'.$path;
        return response()->json(['location' => $url], 201)->header('Cache-Control', 'no-store');
    }

    public function blocks()
    {
        $blocks = DB::table('crash_course_email_blocks')->orderBy('name')->orderBy('id')->get()->map(function ($row) {
            return ['id' => $row->id, 'name' => $row->name, 'block' => json_decode($row->block, true)];
        });
        return response()->json(['blocks' => $blocks])->header('Cache-Control', 'no-store');
    }

    public function storeBlock(Request $request, EmailDesign $designs)
    {
        $request->validate(['name' => 'required|string|max:80', 'block' => 'required|array']);
        $validated = $designs->validate(['version' => 1, 'settings' => $designs->initial()['settings'], 'blocks' => [$request->input('block')]]);
        $id = DB::table('crash_course_email_blocks')->insertGetId([
            'name' => trim($request->input('name')), 'block' => json_encode($validated['blocks'][0]),
            'creator_id' => auth('admin')->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['id' => $id, 'name' => trim($request->input('name')), 'block' => $validated['blocks'][0]], 201);
    }

    public function destroyBlock($block)
    {
        abort_unless(ctype_digit((string) $block), 404);
        DB::table('crash_course_email_blocks')->where('id', $block)->delete();
        return response()->noContent();
    }
}
