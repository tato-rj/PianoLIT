<?php

namespace Tests\Review;

use App\{FavoriteFolder, User};
use App\Http\Controllers\Api\FavoriteFoldersController;
use App\Http\Controllers\WebApp\FavoriteFoldersController as WebFoldersController;
use App\Http\Requests\FavoriteFoldersForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Validator};
use Symfony\Component\HttpKernel\Exception\HttpException;

class FolderRequestsRegressionTest extends ReviewTestCase
{
    private function owner()
    {
        DB::table('users')->insert(['id' => 1, 'first_name' => 'Test', 'last_name' => 'Owner', 'email' => 'test@example.test', 'password' => 'unused']);
        return User::findOrFail(1);
    }

    public function test_web_folder_authorization_and_unchanged_name()
    {
        $user = $this->owner();
        $folder = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice']);
        $this->actingAs($user);
        $form = new FavoriteFoldersForm(['folder_id' => $folder->id, 'name' => 'Practice']);
        $this->assertTrue($form->authorize());
        $this->assertFalse(Validator::make($form->all(), $form->rules())->fails());
        $folder->update(['user_id' => 2]);
        $this->assertFalse($form->authorize());
    }

    public function test_api_can_edit_description_without_renaming_the_folder()
    {
        $this->owner();
        $folder = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice']);
        $response = (new FavoriteFoldersController)->update(new Request([
            'user_id' => 1, 'folder_id' => $folder->id, 'name' => 'Practice', 'description' => 'New description',
        ]));
        $this->assertTrue($response->getData()->valid);
        $this->assertSame('New description', $folder->fresh()->description);
    }

    public function test_body_fields_cannot_override_the_folder_in_the_route()
    {
        $this->actingAs($this->owner());
        $owned = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice']);
        $foreign = FavoriteFolder::create(['user_id' => 2, 'name' => 'Private']);
        $form = new FavoriteFoldersForm(['folder' => $owned->id, 'folder_id' => $owned->id, 'name' => 'Renamed']);
        $route = new \Illuminate\Routing\Route('PATCH', '/folders/{folder}', function () {});
        $route->bind(Request::create('/folders/'.$foreign->id, 'PATCH'));
        $route->setParameter('folder', $foreign);
        $form->setRouteResolver(function () use ($route) { return $route; });
        $this->assertFalse($form->authorize());
    }

    public function test_invalid_reordering_is_rejected_as_validation_feedback()
    {
        $this->owner();
        $folder = FavoriteFolder::create(['user_id' => 1, 'name' => 'Practice']);
        $response = (new FavoriteFoldersController)->reorder(new Request([
            'user_id' => 1, 'folder_id' => $folder->id, 'ids' => 'invalid',
        ]));
        $this->assertFalse($response->getData()->valid);
    }

    public function test_web_pdf_rejects_another_users_folder_before_generating_files()
    {
        $this->actingAs($this->owner());
        $folder = FavoriteFolder::create(['user_id' => 2, 'name' => 'Private']);
        try {
            (new WebFoldersController)->pdf(new Request, $folder);
            $this->fail('Foreign folder was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
