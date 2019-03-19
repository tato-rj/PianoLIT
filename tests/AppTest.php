<?php

namespace Tests;

use App\{Composer, Piece, Admin, Country, Tag, User, Membership};

class AppTest extends TestCase
{
	public function setUp() : void
	{
		parent::setUp();

        $this->admin = create(Admin::class);

        $this->user = create(User::class);

        $this->user->membership()->save(create(Membership::class));

        $this->country = create(Country::class);

        $this->tag = create(Tag::class, ['creator_id' => $this->admin->id]);

        $this->composer = create(Composer::class, [
        	'creator_id' => $this->admin->id,
        	'country_id' => $this->country->id
        ]);

        $this->piece = create(Piece::class, [
            'creator_id' => $this->admin->id,
            'composer_id' => $this->composer->id
        ]);
        
        $this->piece->tags()->attach($this->tag);
	}

    protected function signIn($admin = null, $guard = 'admin')
    {
        $admin = ($admin) ?: $this->admin;
        return $this->actingAs($admin, $guard);
    }
}
