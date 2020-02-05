<?php

namespace Tests\Feature;

use Tests\AppTest;
use Tests\Traits\ManageDatabase;
use App\{User, Subscription, EmailList};
use App\Notifications\User\AccountDeleted;
use App\Rules\Recaptcha;

class UserTest extends AppTest
{
    use ManageDatabase;
    
    /** @test */
    public function unauthenticated_users_cannot_access_a_profile_page()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $this->get(route('users.profile'));
    }

    /** @test */
    public function users_can_signup_from_the_app()
    {
        $this->register();
        
        $this->assertDatabaseHas('users', ['first_name' => 'John']); 
    }

    /** @test */
    public function registration_forms_require_a_recaptcha_if_present()
    {
        unset(app()[Recaptcha::class]);

        $this->register()->assertSessionHas('error');
        
        $this->assertDatabaseMissing('users', ['first_name' => 'John']); 
    }

    /** @test */
    public function users_are_subscribed_to_all_lists_after_registering()
    {
        $this->register();

        $this->assertDatabaseHas('subscriptions', ['email' => auth()->user()->email]);

        $this->assertTrue(EmailList::newsletter()->has(auth()->user()->email));
        $this->assertTrue(EmailList::freepick()->has(auth()->user()->email));
    }

    /** @test */
    public function a_users_favorites_are_removed_when_the_user_is_deleted()
    {
    	$this->signIn($this->user);

    	$userId = $this->user->id;

    	$this->delete(route('users.destroy', $userId));

    	$this->assertDatabaseMissing('favorites', ['user_id' => $userId]);
    }

    /** @test */
    public function a_users_views_are_removed_when_the_user_is_deleted()
    {
    	$this->signIn($this->user);
    	
    	$userId = $this->user->id;

    	$this->assertDatabaseHas('piece_views', ['user_id' => $userId]);

    	$this->delete(route('users.destroy', $userId));

    	$this->assertDatabaseMissing('piece_views', ['user_id' => $userId]);
    }

    /** @test */
    public function users_can_update_their_own_profile()
    {
        $this->signIn(create(User::class));

        $name = auth()->user()->first_name;

        $this->patch(route('users.update', auth()->user()->id), [
            'first_name' => 'NewName',
            'last_name' => auth()->user()->last_name,
            'email' => auth()->user()->email,
        ]);

        $this->assertNotEquals($name, auth()->user()->fresh()->first_name);
    }

    /** @test */
    public function a_users_subscription_is_automatically_updated_when_the_user_changes_the_email()
    {
        $this->signIn(create(User::class));

        create(Subscription::class, ['email' => auth()->user()->email]);

        $oldEmail = auth()->user()->email;

        $this->patch(route('users.update', auth()->user()->id), [
            'first_name' => auth()->user()->first_name,
            'last_name' => auth()->user()->last_name,
            'email' => 'updated@email.com',
        ]);

        $this->assertDatabaseMissing('subscriptions', ['email' => $oldEmail]);
        $this->assertDatabaseHas('subscriptions', ['email' => auth()->user()->fresh()->email]);
    }

    /** @test */
    public function users_can_only_update_their_own_profile()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn(create(User::class));

        $this->patch(route('users.update', $this->user->id), [
            'first_name' => 'NewName',
            'last_name' => $this->user->last_name,
            'email' => $this->user->email,
        ]);
    }

    /** @test */
    public function users_can_remove_their_own_profile()
    {
        $this->signIn(create(User::class));

        $id = auth()->user()->id;

        $this->delete(route('users.destroy', auth()->user()->id));

        $this->assertDatabaseMissing('users', ['id' => $id]);
    }

    /** @test */
    public function admins_are_notified_when_a_user_removes_their_profile()
    {
        \Notification::fake();

        $this->signIn(create(User::class));

        $this->delete(route('users.destroy', auth()->user()));

        \Notification::assertSentTo($this->admin, AccountDeleted::class);
    }

    /** @test */
    public function users_can_only_remove_their_own_profile()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');
        
        $this->signIn(create(User::class));

        $this->delete(route('users.destroy', $this->user->id));
    }
}
