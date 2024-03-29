<?php

namespace Tests\Feature\App;

use Tests\AppTest;
use App\{Piece, FavoriteFolder, Favorite};

class FavoritesTest extends AppTest
{
    /** @test */
    public function app_users_can_toggle_favorites_without_folders()
    {
        Favorite::truncate();

        $initialCount = $this->user->favorites()->count();
        
        $newPiece = create(Piece::class);

        $this->assertEquals(0, Favorite::count());

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => $newPiece]));

        $this->assertNotEquals($initialCount, $this->user->favorites()->count());

        $this->assertEquals(1, Favorite::count());

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => $newPiece]));

        $this->assertEquals($initialCount, $this->user->favorites()->count());

        $this->assertEquals(0, Favorite::count());
    }

    /** @test */
    public function app_users_can_toggle_favorites_from_folders()
    {
        Favorite::truncate();

        create(Favorite::class, ['user_id' => $this->user->id]);

        $this->assertEquals(1, Favorite::count());

        $newPiece = create(Piece::class);

        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => $newPiece, 'folder_id' => $folder->id]));

        $this->assertEquals(2, Favorite::count());

        $this->assertEquals(2, $this->user->favorites()->count());

        $this->assertEquals(1, $this->user->favoriteFolders()->first()->favorites()->count());

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => $newPiece, 'folder_id' => $folder->id]));

        $this->assertEquals(1, Favorite::count());
        
        $this->assertEquals(1, $this->user->favorites()->count());

        $this->assertEquals(0, $this->user->favoriteFolders()->first()->favorites()->count());
    }

    /** @test */
    public function app_users_can_add_a_repeated_favorite_on_a_different_folder()
    {
        $newPiece = create(Piece::class);

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => $newPiece]));

        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => $newPiece, 'folder_id' => $folder->id]));

        $this->assertEquals(3, $this->user->favorites()->count());
    }

    /** @test */
    public function app_users_cannot_add_a_repeated_favorite_on_the_same_folder()
    {
        $this->expectException('Illuminate\Validation\ValidationException');

        $newPiece = create(Piece::class);

        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        Favorite::addTo($this->user, $newPiece, $folder);
        
        Favorite::addTo($this->user, $newPiece, $folder);
    }

    /** @test */
    public function app_users_cannot_toggle_favorites_from_other_users_folders()
    {
        $this->expectException('Illuminate\Validation\ValidationException');

        $newPiece = create(Piece::class);

        $folder = create(FavoriteFolder::class);

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => $newPiece, 'folder_id' => $folder->id]));
    }

    /** @test */
    public function app_users_can_create_folders()
    {
        $this->assertFalse($this->user->favoriteFolders()->exists());

        $this->post(route('api.users.favorites.folders.store', ['user_id' => $this->user->id, 'name' => 'Foo']));

        $this->assertTrue($this->user->favoriteFolders()->exists());
    }

    /** @test */
    public function app_users_cannot_create_two_folders_with_the_same_name()
    {
        $this->signIn($this->user);

        $response = $this->post(route('api.users.favorites.folders.store', ['user_id' => $this->user->id, 'name' => 'Foo']));

        $this->assertTrue($response['valid']);
        $this->assertCount(1, $this->user->favoriteFolders);

        $response = $this->post(route('api.users.favorites.folders.store', ['user_id' => $this->user->id, 'name' => 'Foo']));

        $this->assertFalse($response['valid']);
        $this->assertCount(1, $this->user->favoriteFolders);
    }

    /** @test */
    public function app_users_can_update_their_folders()
    {
        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->patch(route('api.users.favorites.folders.update', ['user_id' => $this->user->id, 'folder_id' => $folder->id, 'name' => 'bar']));

        $this->assertNotEquals($folder->name, $this->user->favoriteFolders->fresh()->first()->name);
    }

    /** @test */
    public function app_users_cannot_update_folders_from_other_users()
    {        
        $folder = create(FavoriteFolder::class);

        $response = $this->patch(route('api.users.favorites.folders.update', ['user_id' => $this->user->id, 'folder_id' => $folder->id, 'name' => 'bar']));

        $this->assertFalse($response['valid']);
        $this->assertNotEquals($folder->name, 'bar');
    }

    /** @test */
    public function app_users_can_delete_their_folders()
    {
        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->assertEquals(1, $this->user->favoriteFolders->count());

        $this->delete(route('api.users.favorites.folders.delete', ['user_id' => $this->user->id, 'folder_id' => $folder->id]));

        $this->assertEquals(0, $this->user->fresh()->favoriteFolders->count());
    }

    /** @test */
    public function app_users_cannot_delete_folders_from_other_users()
    {        
        $folder = create(FavoriteFolder::class);

        $response = $this->delete(route('api.users.favorites.folders.delete', ['user_id' => $this->user->id, 'folder_id' => $folder->id]));

        $this->assertFalse($response['valid']);
        $this->assertDatabaseHas('favorite_folders', ['name' => $folder->name]);
    }

    /** @test */
    public function all_favorites_inside_a_deleted_folder_are_also_removed_from_app_users_favorited_list()
    {
        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => create(Piece::class)->id, 'folder_id' => $folder->id]));
        $this->post(route('api.users.favorites.update', ['user_id' => $this->user->id, 'piece_id' => create(Piece::class)->id, 'folder_id' => $folder->id]));
        
        $this->assertEquals(3, $this->user->favorites()->count());

        $this->assertEquals(2, $this->user->favoriteFolders()->first()->favorites()->count());

        $this->delete(route('api.users.favorites.folders.delete', ['user_id' => $this->user->id, 'folder_id' => $folder->id]));

        $this->assertEquals(1, $this->user->favorites()->count());

        $this->assertFalse($this->user->favoriteFolders()->exists());
    }

    /** @test */
    public function app_users_can_create_a_folder_and_save_a_piece_in_it_at_the_same_time()
    {
        $piece = create(Piece::class);

        $this->post(route('api.users.favorites.folders.store', ['user_id' => $this->user->id, 'name' => 'Foo']));

        $this->assertNotTrue($piece->isFavorited($this->user));

        $this->post(route('api.users.favorites.folders.store', ['user_id' => $this->user->id, 'name' => 'bar', 'piece_id' => $piece->id]));

        $this->assertNotTrue($piece->isFavorited($this->user));
    }
}
