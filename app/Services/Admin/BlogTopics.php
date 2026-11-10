<?php

namespace App\Services\Admin;

use App\Blog\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\{Rule, ValidationException};

class BlogTopics
{
    public function save(Request $request, ?Topic $topic = null)
    {
        $url = app(PostsTable::class)->modalUrl($request, 'topics');
        $name = $request->input('name');
        if (is_string($name)) $name = trim($name);
        $request->merge(['name' => $name]);
        $slug = is_string($name) ? str_slug($name) : '';
        $validator = Validator::make(['name' => $name], [
            'name' => ['required', 'string', 'max:255', Rule::unique('topics', 'name')->ignore($topic ? $topic->id : null)],
        ]);
        $validator->after(function ($validator) use ($topic, $slug) {
            if ($validator->errors()->has('name')) return;
            if ($slug === '') $validator->errors()->add('name', 'Enter a topic name containing a letter or number.');
            elseif (Topic::where('slug', $slug)->when($topic, fn ($query) => $query->where('id', '!=', $topic->id))->exists()) {
                $validator->errors()->add('name', 'A topic with a similar name already exists.');
            }
        });
        if ($validator->fails()) throw (new ValidationException($validator))
            ->errorBag($topic ? 'blogTopic'.$topic->id : 'blogTopicCreate')->redirectTo($url);
        DB::transaction(function () use ($topic, $name, $slug) {
            $record = $topic ? Topic::whereKey($topic->id)->lockForUpdate()->firstOrFail() : new Topic;
            if (!$record->exists) $record->creator_id = auth('admin')->id();
            $record->fill(['name' => $name, 'slug' => $slug])->save();
        });
        return redirect($url)->with('status', $topic ? 'Topic saved.' : 'Topic created.');
    }

    public function delete(Request $request, Topic $topic)
    {
        $filters = app(PostsTable::class)->state($request);
        if ((int) ($filters['topic'] ?? 0) === $topic->id) unset($filters['topic']);
        $url = route('admin.posts.index', $filters + ['manage' => 'topics']);
        DB::transaction(fn () => Topic::whereKey($topic->id)->lockForUpdate()->firstOrFail()->delete());
        return redirect($url)->with('status', 'Topic deleted.');
    }
}
