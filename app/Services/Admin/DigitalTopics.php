<?php

namespace App\Services\Admin;

use App\Shop\{eBookTopic, eScoreTopic};
use App\Support\AdminTableFilters;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\{Rule, ValidationException};

class DigitalTopics
{
    private const MODELS = ['ebooks' => eBookTopic::class, 'escores' => eScoreTopic::class];

    private function model(string $kind): string
    {
        abort_unless(isset(self::MODELS[$kind]), 404);
        return self::MODELS[$kind];
    }

    public function all(string $kind)
    {
        $model = $this->model($kind);
        return $model::ordered()->orderBy('id')->get();
    }

    public function url(Request $request, string $kind): string
    {
        $this->model($kind);
        $filters = app(DigitalProductsTable::class)->filters(Request::create('/', 'GET', $request->query()));
        return route('admin.'.$kind.'.index', AdminTableFilters::query($filters) + ['topics' => '1']);
    }

    public function save(Request $request, string $kind, $topic = null)
    {
        $model = $this->model($kind);
        abort_unless($topic === null || $topic instanceof $model, 404);
        $url = $this->url($request, $kind);
        $bag = $topic ? 'digitalTopic'.$topic->id : 'digitalTopicCreate';
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

    public function delete(Request $request, string $kind, $topic)
    {
        $model = $this->model($kind);
        abort_unless($topic instanceof $model, 404);
        $url = $this->url($request, $kind);
        DB::transaction(function () use ($model, $topic) {
            $model::whereKey($topic->id)->lockForUpdate()->firstOrFail()->delete();
        });
        return redirect($url)->with('status', 'Topic deleted.');
    }
}
