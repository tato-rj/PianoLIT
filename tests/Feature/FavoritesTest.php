<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Piece;

class FavoritesTest extends AppTest
{
    /** @test */
    public function app_users_can_favorite_and_unfavorite_pieces()
    {
        $initialCount = $this->user->favorites()->count();
        
        $newPiece = create(Piece::class);

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => $newPiece]));

        $this->assertNotEquals($initialCount, $this->user->favorites()->count());

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => $newPiece]));

        $this->assertEquals($initialCount, $this->user->favorites()->count());
    }
}
