<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\{HasMembership, Reportable};
use App\Merchandise\Purchase;
use App\Stats\User as UserStats;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasMembership, Reportable;

    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'is_active' => 'boolean',
        'super_user' => 'boolean'
    ];
    protected $dates = ['trial_ends_at', 'email_verified_at'];

    protected static function boot()
    {
        parent::boot();

        self::deleting(function($user) {
            $user->favorites()->detach();
            $user->views()->detach();
        });
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'email', 'email');
    }

    public function membership()
    {
    	return $this->hasOne(Membership::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Piece::class, 'favorites', 'user_id', 'piece_id');
    }

    public function views()
    {
        return $this->belongsToMany(Piece::class, 'piece_views', 'user_id', 'piece_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function getPreferredPieceAttribute()
    {
        return Piece::find($this->preferred_piece_id);
    }

    public function getPreferredLevelAttribute()
    {
        $levels = ['none' => 'beginner', 'some' => 'intermediate', 'a lot' => 'advanced'];

        $level = array_key_exists($this->experience, $levels) ? $levels[$this->experience] : null;

        return $level;
    }

    public function getPreferredMoodAttribute()
    {
        if (! $this->preferred_Piece)
            return null;

        return $this->preferredPiece->tags->where('type', 'mood')->pluck('name');        
    }

    public function tags()
    {
        $tags = [];

        if ($this->preferred_Piece) {
            foreach ($this->preferred_piece->tags as $tag) {
                array_push($tags, $tag->name);
            }
        }

        if (! $this->favorites()->exists() && $this->preferredLevel)
            array_push($tags, $this->preferredLevel, $this->preferredLevel, $this->preferredLevel, $this->preferredMood);

        foreach ($this->favorites as $piece) {
            array_push($tags, $piece->tags->pluck('name'));
        }

        $tags = array_flatten($tags);
        $orderedTags = array_count_values($tags);
        
        arsort($orderedTags);

        return array_keys(array_slice($orderedTags, 0, 6));       
    }

    public function suggestions($limit)
    {
        $tags = $this->tags();

        $suggestions = Piece::search($tags)->limit($limit)->get();

        $suggestions->each(function($piece, $key) use ($suggestions) {
            if ($this->favorites->contains($piece))
                $suggestions->forget($key);

            if (! $this->favorites()->exists() && $piece->tags->whereIn('name', $this->preferredMood)->isEmpty())
                $suggestions->forget($key);

        });

        return $suggestions;
    }

    public function getFullNameAttribute()
    {
        return "$this->first_name $this->last_name";
    }

    public function getProfilePictureAttribute()
    {
        if ($this->password)
            return asset('images/default_avatar.png');

        return "http://graph.facebook.com/{$this->facebook_id}/picture?type=large";
    }

    public function scopeStats($query)
    {
        return new UserStats($this);
    }

    public function scopeExclude($query, $ids)
    {
        return $query->whereNotIn('id', $ids);
    }
    
    public function referralUrl()
    {
        $code = '';

        foreach (str_split($this->email) as $letter) {
            if (ctype_alpha($letter))
                $code .= ord($letter) - 96;
        }

        return route('register', ['referral' => $code]);
    }
}
