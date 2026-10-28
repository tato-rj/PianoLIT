<?php

namespace App\Merchandise;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Support\Facades\Storage;

class MerchandiseImage extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function getUrlAttribute(): string
    {
        $url = Storage::disk($this->disk)->url($this->path);
        if (preg_match('#^https?://#i', $url)) return $url;
        $root = rtrim(config('app.url'), '/');
        if (!preg_match('#^https?://#i', $root)) $root = (app()->environment('production') ? 'https://' : 'http://').$root;
        if (strpos($url, '//') === 0) return parse_url($root, PHP_URL_SCHEME).':'.$url;
        return strpos($url, '/') === 0 ? $root.$url : parse_url($root, PHP_URL_SCHEME).'://'.$url;
    }
}
