<?php

namespace Tests\Review;

use App\{Favorite, FavoriteFolder, Piece, User};
use App\Rules\UserMustOwnTheFolder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FavoritesRegressionTest extends ReviewTestCase
{
    private function favoriteFixture()
    {
        $user = new User(['id' => 1]);
        $piece = new Piece(['id' => 1]);
        $folder = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice']);
        Favorite::create(['user_id' => 1, 'piece_id' => 1]);

        return [$user, $piece, $folder];
    }

    public function test_failed_move_preserves_the_source_favorite()
    {
        [$user, $piece, $folder] = $this->favoriteFixture();
        Favorite::create(['user_id' => 1, 'piece_id' => 1, 'favorite_folder_id' => $folder->id]);

        try {
            Favorite::moveTo($user, $piece, null, $folder);
            $this->fail('A duplicate destination must reject the move.');
        } catch (ValidationException $exception) {
            $this->assertSame(2, Favorite::count());
            $this->assertTrue(Favorite::whereNull('favorite_folder_id')->exists());
        }
    }

    public function test_move_to_another_users_folder_preserves_the_source()
    {
        [$user, $piece, $folder] = $this->favoriteFixture();
        $folder->update(['user_id' => 2]);

        try {
            Favorite::moveTo($user, $piece, null, $folder);
            $this->fail('The destination must belong to the user.');
        } catch (ValidationException $exception) {
            $this->assertTrue(Favorite::whereNull('favorite_folder_id')->exists());
        }
    }

    public function test_successful_move_keeps_exactly_one_favorite()
    {
        [$user, $piece, $folder] = $this->favoriteFixture();
        Favorite::moveTo($user, $piece, null, $folder);
        $this->assertSame(1, Favorite::count());
        $this->assertEquals($folder->id, Favorite::first()->favorite_folder_id);
    }

    public function test_loaded_folder_membership_checks_do_not_query_the_database()
    {
        $folder = new FavoriteFolder;
        $folder->setRelation('favorites', collect([new Favorite(['piece_id' => 7])]));
        DB::enableQueryLog();
        DB::flushQueryLog();
        $folder->hasPiece(7);
        $this->assertTrue($folder->has_piece);
        $folder->hasPiece(8);
        $this->assertFalse($folder->has_piece);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_missing_or_foreign_folder_raises_not_found()
    {
        $folder = FavoriteFolder::create(['user_id' => 2, 'name' => 'Private']);
        $this->expectException(ModelNotFoundException::class);
        FavoriteFolder::flat(1, $folder->id);
    }

    public function test_ownership_rule_returns_false_for_a_missing_user()
    {
        $folder = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice']);
        $this->assertFalse((new UserMustOwnTheFolder($folder->id))->passes('user_id', 999));
        $this->assertTrue((new UserMustOwnTheFolder($folder->id))->passes('user_id', 1));
    }

    public function test_recommendations_allow_a_user_without_favorites()
    {
        $user = new User(['id' => 1]);
        $user->setRelation('favorites', collect());
        $this->assertCount(0, $user->suggestions(20));
    }
}
