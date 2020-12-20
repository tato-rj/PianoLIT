<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\User;

class ReviewsTest extends AppTest
{
    /** @test */
    public function unauthenticated_users_cannot_review_products()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $this->post($this->ebook->reviewRoute(), ['rating' => 5]);
    }

    /** @test */
    public function authenticated_users_can_review_products()
    {
        $this->signIn($this->user);

        $this->assertCount(0, $this->ebook->reviews);

        $this->post($this->ebook->reviewRoute(), ['rating' => 5]);

        $this->assertCount(1, $this->ebook->fresh()->reviews);
    }

    /** @test */
    public function a_user_cannot_review_the_same_product_twice()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        $this->post($this->ebook->reviewRoute(), ['rating' => 5]);

        $this->post($this->ebook->reviewRoute(), ['rating' => 5]);
    }

    /** @test */
    public function multiple_users_can_review_the_same_product()
    {        
        $this->assertFalse($this->ebook->reviews()->exists());

        $this->signIn($this->user);

        $this->post($this->ebook->reviewRoute(), ['rating' => 5]);

        $this->assertCount(1, $this->ebook->reviews);

        $this->signIn(create(User::class));

        $this->post($this->ebook->reviewRoute(), ['rating' => 5]);

        $this->assertCount(2, $this->ebook->fresh()->reviews);
    }

    /** @test */
    public function a_review_does_not_show_up_until_it_is_approved_by_an_admin()
    {
        $this->signIn($this->user);

        $this->post($this->ebook->reviewRoute(), ['rating' => 5, 'title' => 'Awesome product!']);

        $this->get(route('ebooks.show', $this->ebook))->assertDontSee($this->ebook->reviews->first()->title);

        $this->ebook->reviews->first()->updateStatus();

        $this->get(route('ebooks.show', $this->ebook))->assertSee($this->ebook->reviews->first()->title);
    }

    /** @test */
    public function users_can_delete_their_own_review()
    {
        $this->signIn($this->user);

        $this->post($this->ebook->reviewRoute(), ['rating' => 5, 'title' => 'Awesome product!']);
        
        $this->assertCount(1, $this->ebook->reviews);

        $this->delete(route('reviews.destroy', $this->ebook->reviews->first()));
        
        $this->assertCount(0, $this->ebook->fresh()->reviews);        
    }

    /** @test */
    public function users_cannot_delete_reviews_from_other_users()
    {
        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->signIn($this->user);

        $this->post($this->ebook->reviewRoute(), ['rating' => 5, 'title' => 'Awesome product!']);
        
        $this->signIn(create(User::class));

        $this->delete(route('reviews.destroy', $this->ebook->reviews->first()));
    }

    /** @test */
    public function admins_can_delete_any_review()
    {
        $this->signIn($this->user);

        $this->post($this->ebook->reviewRoute(), ['rating' => 5, 'title' => 'Awesome product!']);
        
        $this->assertCount(1, $this->ebook->reviews);

        $this->logout();

        $this->signIn();

        $this->delete(route('admin.reviews.destroy', $this->ebook->reviews->first()));
        
        $this->assertCount(0, $this->ebook->fresh()->reviews);   
    }

    /** @test */
    public function admins_can_submit_many_fake_reviews()
    {
        $this->signIn();

        $this->post($this->ebook->reviewRoute('admin'), ['rating' => 5, 'title' => 'Awesome product!']);

        $this->post($this->ebook->reviewRoute('admin'), ['rating' => 3, 'title' => 'Nice product!']);

        $this->assertTrue($this->ebook->reviews()->first()->isFake());
        $this->assertTrue($this->ebook->reviews()->latest()->first()->isFake());
        $this->assertEquals(2, $this->ebook->reviews()->count());
    }
}
