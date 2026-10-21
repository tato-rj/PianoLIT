<?php

namespace App\Services\Admin;

use App\PlaylistTag;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\ValidationException;

class PlaylistTags
{
    public function all()
    {
        return PlaylistTag::withCount('playlists')->orderBy('name')->orderBy('id')->get();
    }

    /** Call inside the playlist write transaction, before locking playlists. */
    public function assignment($id)
    {
        if ($id === null) return ['featured_tag_id' => null, 'featured' => null];
        $tag = PlaylistTag::whereKey($id)->lockForUpdate()->first();
        if (!$tag) throw ValidationException::withMessages(['featured_tag_id' => 'Choose an existing featured tag.']);
        return ['featured_tag_id' => $tag->id, 'featured' => $tag->name];
    }

    public function save($name, ?int $id = null)
    {
        $name = is_string($name) ? trim($name) : $name;
        Validator::make(['name' => $name], ['name' => 'required|string|max:255'])->validate();
        $key = hash('sha256', $name);
        if (PlaylistTag::where('name_key', $key)->when($id, function ($query) use ($id) { $query->where('id', '!=', $id); })->exists()) $this->duplicate();
        try {
            return DB::transaction(function () use ($name, $key, $id) {
                $tag = $id === null ? new PlaylistTag : PlaylistTag::whereKey($id)->lockForUpdate()->firstOrFail();
                $tag->fill(['name' => $name, 'name_key' => $key])->save();
                DB::table('playlists')->where('featured_tag_id', $tag->id)->update(['featured' => $name, 'updated_at' => now()]);
                return $tag;
            }, 3);
        } catch (QueryException $exception) {
            // The unique key also handles two administrators creating the same name.
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)
                && PlaylistTag::where('name_key', $key)->when($id, function ($query) use ($id) { $query->where('id', '!=', $id); })->exists()) $this->duplicate();
            throw $exception;
        }
    }

    public function delete(int $id)
    {
        DB::transaction(function () use ($id) {
            $tag = PlaylistTag::whereKey($id)->lockForUpdate()->firstOrFail();
            DB::table('playlists')->where('featured_tag_id', $id)->update(['featured_tag_id' => null, 'featured' => null, 'updated_at' => now()]);
            $tag->delete();
        }, 3);
    }

    private function duplicate()
    {
        throw ValidationException::withMessages(['name' => 'A tag with this name already exists.']);
    }
}
