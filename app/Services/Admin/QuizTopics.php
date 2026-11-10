<?php

namespace App\Services\Admin;

use App\Quiz\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\{Rule, ValidationException};

class QuizTopics
{
    public function save(Request $request, ?Topic $topic = null)
    {
        $url = app(QuizzesTable::class)->url($request, 'topics');
        $bag = $topic ? 'quizTopic'.$topic->id : 'quizTopicCreate';
        $name = $request->input('name');
        if (is_string($name)) $name = trim($name);
        $request->merge(['name' => $name]);
        $slug = is_string($name) ? str_slug($name) : '';
        $validator = Validator::make(['name' => $name], [
            'name' => ['required', 'string', 'max:255', Rule::unique('quiz_topics', 'name')->ignore(optional($topic)->id)],
        ]);
        $validator->after(function ($validator) use ($slug, $topic) {
            if ($validator->errors()->has('name')) return;
            if ($slug === '') $validator->errors()->add('name', 'Enter a topic name containing a letter or number.');
            elseif (Topic::where('slug', $slug)->when($topic, fn ($q) => $q->where('id', '!=', $topic->id))->exists()) {
                $validator->errors()->add('name', 'A topic with a similar name already exists.');
            }
        });
        if ($validator->fails()) throw (new ValidationException($validator))->errorBag($bag)->redirectTo($url);
        DB::transaction(function () use ($topic, $name, $slug) {
            $record = $topic ? Topic::whereKey($topic->id)->lockForUpdate()->firstOrFail() : new Topic;
            if (!$record->exists) $record->creator_id = auth('admin')->id();
            $record->fill(['name' => $name, 'slug' => $slug])->save();
        });
        return redirect($url)->with('status', $topic ? 'Topic saved.' : 'Topic created.');
    }

    public function delete(Request $request, Topic $topic)
    {
        $url = app(QuizzesTable::class)->url($request, 'topics');
        DB::transaction(fn () => Topic::whereKey($topic->id)->lockForUpdate()->firstOrFail()->delete());
        // A removed topic can no longer remain selected as a directory filter.
        $query = $request->query();
        if ((string) ($query['topic_id'] ?? '') === (string) $topic->id) unset($query['topic_id']);
        return redirect(app(QuizzesTable::class)->url(Request::create('/', 'GET', $query), 'topics'))
            ->with('status', 'Topic deleted.');
    }
}
