<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\{Performance, User, Piece};
use App\Notifications\Performances\{PerformanceSubmittedNotification, PerformanceDeletedNotification};
use App\Mail\Performances\{PerformanceSubmittedEmail, PerformanceApprovedEmail};
use App\Events\Performances\PerformanceSubmitted;

class PerformanceTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
        
        \Mail::fake();
        \Notification::fake();
    }

    /** @test */
    public function admins_are_notified_when_a_performance_is_uploaded()
    {
        $this->signIn($this->user);

        event(new PerformanceSubmitted(create(Performance::class)));

        \Notification::assertSentTo($this->admin, PerformanceSubmittedNotification::class);
    }

    /** @test */
    public function a_performance_lists_as_pending_until_an_admin_confirms_it()
    {
        $this->signIn($this->user);

        $this->assertFalse(auth()->user()->performances()->pending()->exists());

        create(Performance::class, ['user_id' => auth()->user(), 'video_url' => 'foo']);

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

        event(new PerformanceSubmitted(create(Performance::class)));

        \Mail::assertQueued(PerformanceSubmittedEmail::class);
    }

    /** @test */
    public function users_receive_an_email_when_their_performance_has_been_approved()
    {
        $this->signIn($this->user);

        create(Performance::class, ['user_id' => auth()->user()]);

        $this->signIn();

        $this->patch(route('admin.users.performances.approve', Performance::first()));

        \Mail::assertQueued(PerformanceApprovedEmail::class);
    }

    /** @test */
    public function users_cannot_delete_other_users_performances()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        create(Performance::class, ['user_id' => auth()->user()]);
        
        $this->signIn(create(User::class));

        $this->delete(route('webapp.users.performances.destroy', Performance::first()));
    }

    /** @test */
    public function users_cannot_clap_to_their_own_piece()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        create(Performance::class, ['user_id' => auth()->user()]);
        
        $this->post(route('api.users.performances.clap', Performance::first()), ['user_id' => auth()->user()->id]);
    }

    /** @test */
    public function users_can_clap_many_times_to_the_same_piece()
    {
        $this->signIn($this->user);

        create(Performance::class, ['user_id' => auth()->user()]);
        
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

        create(Performance::class, ['user_id' => auth()->user()]);

        $performance = Performance::first();
        
        $this->signIn(create(User::class));

        $this->post(route('api.users.performances.clap', $performance), ['user_id' => auth()->user()->id]);

        $this->signIn($this->user);

        $this->assertDatabaseHas('claps', ['performance_id' => $performance->id]);

        $performance->delete();

        $this->assertDatabaseMissing('claps', ['performance_id' => $performance->id]);
    }

    /** @test */
    public function users_cannot_submit_more_than_one_performance_per_piece()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        create(Performance::class, ['user_id' => auth()->user(), 'piece_id' => $this->piece]);

        $this->post(route('webapp.users.performances.store', $this->piece), ['user-performance-video' => 'foo']);
    }

    // /** @test */
    // public function users_cannot_submit_more_than_one_performance_per_month()
    // {
    //     $this->expectException('Illuminate\Auth\Access\AuthorizationException');

    //     $this->signIn($this->user);

    //     create(Performance::class, ['user_id' => auth()->user()]);

    //     $this->post(route('webapp.users.performances.store', create(Piece::class)), ['user-performance-video' => 'foo']);
    // }
}
