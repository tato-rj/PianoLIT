<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\{Performance, User, Piece};
use Tests\Traits\InteractsWithCloudinary;
use App\Cloudinary\CloudinarySandbox;
use App\Notifications\Performances\{PerformanceSubmittedNotification, PerformanceDeletedNotification};
use App\Mail\Performances\{PerformanceSubmittedEmail, PerformanceApprovedEmail};

class PerformanceTest extends AppTest
{
    use InteractsWithCloudinary;

    public function setUp() : void
    {
        parent::setUp();
        
        \Mail::fake();
        \Notification::fake();
    }

    /** @test */
    public function a_performance_upload_runs_async_and_waits_for_cloudinary_to_complete_the_upload()
    {
        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        $this->assertTrue(auth()->user()->performances()->processing()->exists());
        $this->assertFalse(auth()->user()->performances()->pending()->exists());

        $this->fakeCloudinaryWebhook();

        $this->assertFalse(auth()->user()->performances()->processing()->exists());
        $this->assertTrue(auth()->user()->performances()->pending()->exists());
    }

    /** @test */
    public function admins_are_notified_when_a_performance_is_uploaded()
    {
        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        \Notification::assertSentTo($this->admin, PerformanceSubmittedNotification::class);
    }

    /** @test */
    public function a_performance_lists_as_pending_until_an_admin_confirms_it()
    {
        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        $this->assertFalse(auth()->user()->performances()->pending()->exists());

        $this->fakeCloudinaryWebhook();

        $this->assertTrue(auth()->user()->performances()->pending()->exists());
        $this->assertFalse(auth()->user()->performances()->approved()->exists());

        $performance = auth()->user()->performances()->pending()->first();

        $this->signIn();

        $this->patch(route('admin.users.performances.approve', $performance));

        $this->signIn($this->user);

        $this->assertFalse(auth()->user()->performances()->pending()->exists());
        $this->assertTrue(auth()->user()->performances()->approved()->exists());
    }

    /** @test */
    public function users_receive_an_email_confirming_that_their_performance_was_received_and_pending_approval()
    {
        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        $this->fakeCloudinaryWebhook();

        \Mail::assertQueued(PerformanceSubmittedEmail::class);
    }

    /** @test */
    public function users_receive_an_email_when_their_performance_has_been_approved()
    {
        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        $this->fakeCloudinaryWebhook();

        $this->signIn();

        $this->patch(route('admin.users.performances.approve', Performance::first()));

        \Mail::assertQueued(PerformanceApprovedEmail::class);
    }

    /** @test */
    public function an_admin_can_delete_a_performance()
    {
        $this->signIn($this->user);

        $this->assertDatabaseMissing('performances', ['user_id' => auth()->user()->id]);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        $this->assertDatabaseHas('performances', ['user_id' => auth()->user()->id]);

        $this->signIn();

        $this->assertCount(1, Performance::all());
        
        $this->delete(route('admin.users.performances.destroy', Performance::first()));

        $this->assertCount(0, Performance::all());
    }

        /** @test */
    public function users_can_delete_their_own_performance()
    {
        $this->signIn($this->user);

        $this->assertDatabaseMissing('performances', ['user_id' => auth()->user()->id]);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        $this->assertDatabaseHas('performances', ['user_id' => auth()->user()->id]);
        
        $this->delete(route('webapp.users.performances.destroy', Performance::first()));

        $this->assertCount(0, Performance::all());
    }

        /** @test */
    public function users_cannot_delete_other_users_performances()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);
        
        $this->signIn(create(User::class));

        $this->delete(route('webapp.users.performances.destroy', Performance::first()));
    }

    /** @test */
    public function users_cannot_clap_to_their_own_piece()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);
        
        $this->post(route('api.users.performances.clap', Performance::first()), ['user_id' => auth()->user()->id]);
    }

    /** @test */
    public function users_can_clap_many_times_to_the_same_piece()
    {
        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);
        
        $this->signIn(create(User::class));

        $this->post(route('api.users.performances.clap', Performance::first()), ['user_id' => auth()->user()->id]);

        $this->assertEquals(1, Performance::first()->claps()->sum('count'));

        $this->post(route('api.users.performances.clap', Performance::first()), ['user_id' => auth()->user()->id]);

        $this->assertEquals(2, Performance::first()->claps()->sum('count'));
    }

    /** @test */
    public function when_a_performance_is_deleted_its_claps_records_are_also_removed()
    {
        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        $performance = Performance::first();
        
        $this->signIn(create(User::class));

        $this->post(route('api.users.performances.clap', $performance), ['user_id' => auth()->user()->id]);

        $this->signIn($this->user);

        $this->assertDatabaseHas('claps', ['performance_id' => $performance->id]);

        $this->delete(route('webapp.users.performances.destroy', $performance));

        $this->assertDatabaseMissing('claps', ['performance_id' => $performance->id]);
    }

    /** @test */
    public function users_cannot_submit_more_than_one_performance_per_piece()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);
    }

    /** @test */
    public function users_cannot_submit_more_than_one_performance_per_month()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => $this->uploadedVideo()]);

        $this->post(route('webapp.users.performances.store', create(Piece::class)), ['user-performance-video' => $this->uploadedVideo()]);
    }
}
