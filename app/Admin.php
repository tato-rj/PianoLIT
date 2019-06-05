<?php

namespace App;

use App\Blog\Post;
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

    public function getAlert()
    {
        $alert = [];
        $total_count = Piece::count();
        $levelsStats = Tag::levels()->withCount('pieces')->get();
        $periodsStats = Tag::periods()->withCount('pieces')->get();

        foreach ($levelsStats as $stat) {
            if (percentage($stat->pieces_count, $total_count) < 15) {
                array_push($alert, $stat->name);
            }
        }

        foreach ($periodsStats as $stat) {
            if (percentage($stat->pieces_count, $total_count) < 10) {
                array_push($alert, $stat->name);
            }
        }

        $alertCount = count($alert);

        if ($alertCount == 0)
            return null;

        if ($alertCount == 1) {
            $sentence = $alert[0] . '.';
        } else {
            $partial = array_slice($alert, 0, $alertCount-1);
            $sentence = implode(', ', $partial) . ' and ' . $alert[$alertCount-1];
        }

        return $sentence;
    }
}
