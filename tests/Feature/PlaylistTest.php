<?php

namespace Tests\Feature;

use Tests\AppTest;
use Tests\Traits\ManageDatabase;
use App\{Piece, Admin, Playlist};

class PlaylistTest extends AppTest
{
    /** @test */
    public function an_admin_can_create_a_new_playlist()
    {
        $this->signIn();

        $request = make(Playlist::class);

        $this->post(route('admin.playlists.store'), $request->toArray());

        $this->assertDatabaseHas('playlists', ['name' => $request->name]);
        $this->assertEquals(Playlist::count(), 2);
    }

    /** @test */
    public function an_admin_can_add_pieces_to_a_playlist()
    {
        $this->signIn();
        $id1 = create(Piece::class)->id;
        $id2 = create(Piece::class)->id;
        $id3 = create(Piece::class)->id;
        $pieces = [$id3, $id1, $id2];

        $this->assertCount(1, $this->playlist->pieces);

        $this->patch(route('admin.playlists.update', $this->playlist->id), [
            'name' => $this->playlist->name, 
            'group' => $this->playlist->group, 
            'description' => $this->playlist->description,
            'subtitle' => $this->playlist->subtitle,
            'pieces' => $pieces]);

        $this->assertCount(3, $this->playlist->fresh()->pieces);
        $this->assertEquals($pieces, $this->playlist->fresh()->pieces->pluck('id')->toArray());
    }

    /** @test */
    public function two_playlists_cannot_be_featured_at_the_same_time()
    {
        $this->signIn();

        $featuredPlaylist = create(Playlist::class, ['featured' => 'featured']);

        $this->patch(route('admin.playlists.update', $this->playlist->id), [
            'name' => $this->playlist->name, 
            'group' => $this->playlist->group, 
            'description' => $this->playlist->description,
            'subtitle' => $this->playlist->subtitle,
            'featured' => 'should fail']);

        $this->assertFalse($this->playlist->fresh()->is_featured);

        $featuredPlaylist->update(['featured' => null]);

        $this->patch(route('admin.playlists.update', $this->playlist->id), [
            'name' => $this->playlist->name, 
            'group' => $this->playlist->group, 
            'description' => $this->playlist->description,
            'subtitle' => $this->playlist->subtitle,
            'featured' => 'should work']);

        $this->assertTrue($this->playlist->fresh()->is_featured);
    }

    /** @test */
    public function playlists_can_be_reordered()
    {
        $this->signIn();
        
        Playlist::truncate();

        $first = create(Playlist::class, ['group' => 'test', 'order' => 0]);
        $second = create(Playlist::class, ['group' => 'test', 'order' => 1]);
        $third = create(Playlist::class, ['group' => 'test', 'order' => 2]);

        $this->assertEquals([$first->id, $second->id, $third->id], Playlist::byGroup('test')->sorted()->pluck('id')->toArray());

        $this->patch(route('admin.playlists.reorder', ['ids' => [2, 3, 1]]));

        $this->assertEquals([$second->id, $third->id, $first->id], Playlist::byGroup('test')->sorted()->pluck('id')->toArray());
    }

    /** @test */
    public function all_related_pieces_are_removed_when_a_playlist_is_removed()
    {
        $this->signIn();

        $this->assertNotEmpty(Piece::has('playlists')->get());

        $this->delete(route('admin.playlists.destroy', $this->playlist->id));

        $this->assertEmpty(Piece::has('playlists')->get());        
    }
}
