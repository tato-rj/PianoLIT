<?php

namespace Tests\Feature\WebApp;

use Tests\AppTest;
use App\{Piece, FavoriteFolder, Favorite};

class FavoritesTest extends AppTest
{
    /** @test */
    public function webapp_users_can_toggle_favorites_without_folders()
    {
        $this->signIn($this->user);

        $initialCount = $this->user->favorites()->count();
        
        $newPiece = create(Piece::class);

        $this->post(route('webapp.users.favorites.update', $newPiece));

        $this->assertNotEquals($initialCount, $this->user->favorites()->count());

        $this->post(route('webapp.users.favorites.update', $newPiece));

        $this->assertEquals($initialCount, $this->user->favorites()->count());
    }

    /** @test */
    public function webapp_users_can_toggle_favorites_from_folders()
    {
        $this->signIn($this->user);

        $newPiece = create(Piece::class);

        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->post(route('webapp.users.favorites.update', ['piece' => $newPiece, 'folder_id' => $folder->id]));
         
        $this->assertEquals(2, $this->user->favorites()->count());

        $this->assertEquals(1, $this->user->favoriteFolders()->first()->favorites()->count());

        $this->post(route('webapp.users.favorites.update', ['piece' => $newPiece, 'folder_id' => $folder->id]));
         
        $this->assertEquals(1, $this->user->favorites()->count());

        $this->assertEquals(0, $this->user->favoriteFolders()->first()->favorites()->count());
    }

    /** @test */
    public function webapp_users_can_add_a_repeated_favorite_on_a_different_folder()
    {
        $this->signIn($this->user);

        $newPiece = create(Piece::class);

        $this->post(route('webapp.users.favorites.update', $newPiece));

        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->post(route('webapp.users.favorites.update', ['piece' => $newPiece, 'folder_id' => $folder->id]));

        $this->assertEquals(3, $this->user->favorites()->count());
    }

    /** @test */
    public function webapp_users_cannot_add_a_repeated_favorite_on_the_same_folder()
    {
        $this->signIn($this->user);

        $this->expectException('Illuminate\Validation\ValidationException');

        $newPiece = create(Piece::class);

        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        Favorite::addTo($this->user, $newPiece, $folder);
        
        Favorite::addTo($this->user, $newPiece, $folder);
    }

    /** @test */
    public function webapp_users_cannot_toggle_favorites_from_other_users_folders()
    {
        $this->signIn($this->user);

        $this->expectException('Illuminate\Validation\ValidationException');

        $newPiece = create(Piece::class);

        $folder = create(FavoriteFolder::class);

        $this->post(route('webapp.users.favorites.update', ['piece' => $newPiece, 'folder_id' => $folder->id]));
    }

    /** @test */
    public function webapp_users_can_create_folders()
    {
        $this->signIn($this->user);

        $this->assertFalse($this->user->favoriteFolders()->exists());

        $this->post(route('webapp.users.favorites.folders.store', ['name' => 'Foo']));

        $this->assertTrue($this->user->favoriteFolders()->exists());
    }

    /** @test */
    public function webapp_users_can_update_their_folders()
    {
        $this->signIn($this->user);

        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->patch(route('webapp.users.favorites.folders.update', ['folder' => $folder, 'name' => 'Bar']));

        $this->assertNotEquals($folder->name, $this->user->favoriteFolders->fresh()->first()->name);
    }

    /** @test */
    public function webapp_users_cannot_update_folders_from_other_users()
    {
        $this->signIn($this->user);

        $this->expectException('Illuminate\Validation\ValidationException');
        
        $folder = create(FavoriteFolder::class);

        $this->patch(route('webapp.users.favorites.folders.update', ['folder' => $folder, 'name' => 'Bar']));
    }

    /** @test */
    public function webapp_users_can_delete_their_folders()
    {
        $this->signIn($this->user);

        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->assertEquals(1, $this->user->favoriteFolders->count());

        $this->delete(route('webapp.users.favorites.folders.delete', $folder));

        $this->assertEquals(0, $this->user->fresh()->favoriteFolders->count());
    }

    /** @test */
    public function webapp_users_cannot_delete_folders_from_other_users()
    {
        $this->signIn($this->user);

        $this->expectException('Illuminate\Validation\ValidationException');
        
        $folder = create(FavoriteFolder::class);

        $this->delete(route('webapp.users.favorites.folders.delete', $folder));
    }

    /** @test */
    public function all_favorites_inside_a_deleted_folder_are_also_removed_from_webapp_users_favorited_list()
    {
        $this->signIn($this->user);

        $folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);

        $this->post(route('webapp.users.favorites.update', ['piece' => create(Piece::class), 'folder_id' => $folder->id]));
        $this->post(route('webapp.users.favorites.update', ['piece' => create(Piece::class), 'folder_id' => $folder->id]));
        
        $this->assertEquals(3, $this->user->favorites()->count());

        $this->assertEquals(2, $this->user->favoriteFolders()->first()->favorites()->count());

        $this->delete(route('webapp.users.favorites.folders.delete', $folder));

        $this->assertEquals(1, $this->user->favorites()->count());

        $this->assertFalse($this->user->favoriteFolders()->exists());
    }

    /** @test */
    public function webapp_users_can_create_a_folder_and_save_a_piece_in_it_at_the_same_time()
    {
        $this->signIn($this->user);

        $piece = create(Piece::class);

        $this->post(route('webapp.users.favorites.folders.store', ['name' => 'Foo']));

        $this->assertNotTrue($piece->isFavorited($this->user));

        $this->post(route('webapp.users.favorites.folders.store', ['name' => 'Bar', 'piece_id' => $piece->id]));

        $this->assertNotTrue($piece->isFavorited($this->user));
    }
}
