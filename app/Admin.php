<?php

namespace App;

use App\Blog\Post;
use App\Tools\Alerts;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];

    public static function boot()
    {
        parent::boot();

        self::deleting(function($admin) {
            $admin->pieces->each->update(['creator_id' => null]);
        });
    }

    public function pieces()
    {
        return $this->hasMany(Piece::class, 'creator_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'creator_id');
    }

    public function composers()
    {
        return $this->hasMany(Composer::class, 'creator_id');
    }

    public function tags()
    {
        return $this->hasMany(Tag::class, 'creator_id');
    }

    public function getFirstNameAttribute()
    {
        return explode(' ', $this->name)[0];
    }

    public function scopeEditors($query)
    {
        return $query->where('role', 'editor');
    }

    public function scopeManagers($query)
    {
        return $query->where('role', 'manager');
    }

    public function isManager()
    {
        return $this->role == 'manager';
    }

    public function getAlert($alerts = [])
    {
        return (new Alerts)->generate($alerts);
    }
}
