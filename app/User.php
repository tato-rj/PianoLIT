<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\{HasMembership, Reportable};
use App\Contracts\Merchandise;
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
        return $this->belongsToMany(Piece::class, 'favorites', 'user_id', 'piece_id')->with(['composer', 'favorites']);
    }

    public function views()
    {
        return $this->belongsToMany(Piece::class, 'piece_views', 'user_id', 'piece_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function studioPolicies()
    {
        return $this->hasMany(StudioPolicy::class);
    }

    public function tutorialRequests()
    {
        return $this->hasMany(TutorialRequest::class);
    }

    public function pendingTutorialRequests()
    {
        return $this->tutorialRequests()->whereNull('published_at');
    }

    public function publishedTutorialRequests()
    {
        return $this->tutorialRequests()->whereNotNull('published_at');
    }

    public function purchasesOf(Merchandise $item)
    {
        return $this->purchases()->where(['item_id' => $item->id, 'item_type' => get_class($item)]);
    }

    public function purchase(Merchandise $item)
    {
        if (! $this->purchasesOf($item)->where('created_at', now())->exists())
            $this->purchases()->create(['item_type' => get_class($item), 'item_id' => $item->id]);

        return $item;
    }

    public function getPreferredPieceAttribute()
    {
        return Piece::find($this->preferred_piece_id);
    }

    public function getPreferredLevelAttribute()
    {
        $levels = ['elementary', 'beginner', 'intermediate', 'advanced'];
        $preferences = ['none' => 1, 'some' => 3, 'a lot' => 4];

        $preferredLevel = array_key_exists($this->experience, $preferences) ? $preferences[$this->experience] : 0;
        
        $favoritesLevel = intval(floor($this->favorites->avg('level_number')));

        $key = avg([$preferredLevel, $favoritesLevel]) - 1;

        return array_key_exists($key, $levels) ? $levels[$key] : null;
    }

    public function getPreferredMoodAttribute()
    {
        if (! $this->preferred_piece)
            return null;

        return $this->preferred_piece->tags->where('type', 'mood')->pluck('name');        
    }

    public function tags($string = false)
    {
        $tags = [];

        array_push($tags, $this->preferred_mood);

        foreach ($this->favorites as $piece) {
            array_push($tags, $piece->tags->where('type', 'mood')->pluck('name'));
        }

        $tags = array_flatten($tags);

        $tags = array_count_values($tags);
        
        arsort($tags);

        $tags = array_keys(array_slice($tags, 0, 2));

        array_push($tags, $this->preferred_level);

        return $string ? implode(' ', $tags) : $tags;
    }

    public function suggestions($limit)
    {
        return Piece::search($this->tags($string = true))
                    ->generic()
                    ->get()
                    // ->favorited(false, $this->id)
                    ->load(['tags', 'composer', 'favorites'])
                    // ->scoutRelevance()
                    ->take($limit);
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
