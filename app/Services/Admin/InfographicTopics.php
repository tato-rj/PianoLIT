<?php

namespace App\Services\Admin;

use App\Infograph\Topic;
use App\Support\AdminTableFilters;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\{Rule, ValidationException};

class InfographicTopics
{
    public function url(Request $request): string
    {
        $filters = app(InfographicsTable::class)->filters(Request::create('/', 'GET', $request->query()));
        return route('admin.infographs.index', AdminTableFilters::query($filters) + ['topics' => '1']);
    }

    public function save(Request $request, $topic = null)
    {
        $model = Topic::class;
        abort_unless($topic === null || $topic instanceof $model, 404);
        $url = $this->url($request);
        $bag = $topic ? 'infographicTopic'.$topic->id : 'infographicTopicCreate';
        $name = $request->input('name');
        if (is_string($name)) $name = trim($name);
        $request->merge(['name' => $name]);
        $slug = is_string($name) ? str_slug($name) : '';
        $validator = Validator::make(['name' => $name], [
            'name' => ['required', 'string', 'max:255', Rule::unique((new $model)->getTable(), 'name')->ignore($topic ? $topic->id : null)],
        ]);
        $validator->after(function ($validator) use ($model, $slug, $topic) {
            if ($validator->errors()->has('name')) return;
            if ($slug === '') $validator->errors()->add('name', 'Enter a topic name containing a letter or number.');
            elseif ($model::where('slug', $slug)->when($topic, fn ($query) => $query->where('id', '!=', $topic->id))->exists()) {
                $validator->errors()->add('name', 'A topic with a similar name already exists.');
            }
        });
        if ($validator->fails()) throw (new ValidationException($validator))->errorBag($bag)->redirectTo($url);
        try {
            DB::transaction(function () use ($model, $topic, $name, $slug) {
                $record = $topic ? $model::whereKey($topic->id)->lockForUpdate()->firstOrFail() : new $model;
                if (!$record->exists) $record->creator_id = auth('admin')->id();
                $record->fill(['name' => $name, 'slug' => $slug])->save();
            });
        } catch (QueryException $exception) {
            if (!in_array((string) $exception->getCode(), ['23000', '23505'], true)
                || !$model::where('name', $name)->when($topic, fn ($query) => $query->where('id', '!=', $topic->id))->exists()) throw $exception;
            $validator->errors()->add('name', 'A topic with this name already exists.');
            throw (new ValidationException($validator))->errorBag($bag)->redirectTo($url);
        }
        return redirect($url)->with('status', $topic ? 'Topic saved.' : 'Topic created.');
    }

    public function delete(Request $request, $topic)
    {
        $model = Topic::class;
        abort_unless($topic instanceof $model, 404);
        $query = $request->query();
        if (isset($query['topic_id']) && (string) $query['topic_id'] === (string) $topic->id) unset($query['topic_id']);
        $url = $this->url(Request::create('/', 'GET', $query));
        DB::transaction(function () use ($model, $topic) {
            $model::whereKey($topic->id)->lockForUpdate()->firstOrFail()->delete();
        });
        return redirect($url)->with('status', 'Topic deleted.');
    }
}
