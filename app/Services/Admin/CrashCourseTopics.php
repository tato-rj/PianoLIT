<?php

namespace App\Services\Admin;

use App\CrashCourse\CrashCourseTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\{Rule, ValidationException};

class CrashCourseTopics
{
    public function save(Request $request, ?CrashCourseTopic $topic = null)
    {
        $url = app(CrashCoursesTable::class)->modalUrl($request);
        $name = $request->input('name');
        if (is_string($name)) $name = trim($name);
        $request->merge(['name' => $name]);
        $slug = is_string($name) ? str_slug($name) : '';
        $validator = Validator::make(['name' => $name], [
            'name' => ['required', 'string', 'max:255', Rule::unique('crash_course_topics', 'name')->ignore(optional($topic)->id)],
        ]);
        $validator->after(function ($validator) use ($topic, $slug) {
            if ($validator->errors()->has('name')) return;
            if ($slug === '') $validator->errors()->add('name', 'Enter a topic name containing a letter or number.');
            elseif (CrashCourseTopic::where('slug', $slug)->when($topic, fn ($q) => $q->where('id', '!=', $topic->id))->exists()) {
                $validator->errors()->add('name', 'A topic with a similar name already exists.');
            }
        });
        if ($validator->fails()) throw (new ValidationException($validator))
            ->errorBag($topic ? 'crashCourseTopic'.$topic->id : 'crashCourseTopicCreate')->redirectTo($url);
        DB::transaction(function () use ($topic, $name, $slug) {
            $record = $topic ? CrashCourseTopic::whereKey($topic->id)->lockForUpdate()->firstOrFail() : new CrashCourseTopic;
            if (!$record->exists) $record->creator_id = auth('admin')->id();
            $record->fill(['name' => $name, 'slug' => $slug])->save();
        });
        return redirect($url)->with('status', $topic ? 'Topic saved.' : 'Topic created.');
    }

    public function delete(Request $request, CrashCourseTopic $topic)
    {
        $filters = app(CrashCoursesTable::class)->state($request);
        if ((string) ($filters['topic_id'] ?? '') === (string) $topic->id) unset($filters['topic_id']);
        DB::transaction(fn () => CrashCourseTopic::whereKey($topic->id)->lockForUpdate()->firstOrFail()->delete());
        return redirect()->route('admin.crashcourses.index', $filters + ['manage' => 'topics'])->with('status', 'Topic deleted.');
    }
}
