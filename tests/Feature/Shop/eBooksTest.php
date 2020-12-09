<?php

namespace Tests\Feature\Shop;

use Tests\AppTest;
use Tests\Traits\{InteractsWithStripe, AdminEvents};
use App\Shop\eBook;
use App\Mail\Shop\ConfirmPurchase;
use App\Notifications\NewPurchaseCompleted;
use Illuminate\Http\UploadedFile;
use App\User;

class eBooksTest extends AppTest
{
	use InteractsWithStripe, AdminEvents;

    /** @test */
    public function an_admin_can_create_a_new_ebook()
    {
        $this->signIn();

        $ebook = $this->storeEBook();

        $this->assertDatabaseHas('e_books', ['title' => $ebook->title]);

        $this->assertNotNull($ebook->cover_path);

        \Storage::disk('public')->assertExists($ebook->cover_path);
    }

    /** @test */
    public function an_admin_cannot_create_two_ebooks_with_the_same_name()
    {
        $this->expectException('Illuminate\Validation\ValidationException');
        
        $this->signIn();

        $ebook1 = $this->storeEBook();

        $ebook2 = make(eBook::class, ['title' => $ebook1->title]);

        $this->post(route('admin.ebooks.store'), $ebook2->toArray());
    }

    /** @test */
    public function an_admin_can_edit_an_existing_ebook()
    {
        $this->signIn();

        $ebook = $this->storeEBook();

        $originalTitle = $ebook->title;

        $originalCover = $ebook->cover_path;

        $originalPDF = $ebook->pdf_path;

        $originalEPUB = $ebook->epub_path;

        $update = make(eBook::class, [
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'epub_file' => UploadedFile::fake()->image('ebook.epub'),
            'pdf_file' => UploadedFile::fake()->image('ebook.pdf'),
        ]);

        $this->patch(route('admin.ebooks.update', $ebook), $update->toArray());

        $this->assertNotEquals($originalTitle, $ebook->fresh()->title);

        $this->assertNotEquals($originalPDF, $ebook->fresh()->pdf_path);

        $this->assertNotEquals($originalEPUB, $ebook->fresh()->epub_path);
        
        \Storage::disk('public')->assertMissing($originalCover);

        \Storage::disk('public')->assertMissing($originalPDF);

        \Storage::disk('public')->assertMissing($originalEPUB);

        \Storage::disk('public')->assertExists($ebook->fresh()->cover_path);

        \Storage::disk('public')->assertExists($ebook->fresh()->pdf_path);

        \Storage::disk('public')->assertExists($ebook->fresh()->epub_path);
    }

    /** @test */
    public function images_are_updated_only_if_a_new_one_is_selected()
    {
        $this->signIn();

        $ebook = $this->storeEBook();

        $originalTitle = $ebook->title;
        $originalCover = $ebook->cover_path;

        $update = make(eBook::class, ['cover_image' => null]);

        $this->patch(route('admin.ebooks.update', $ebook), $update->toArray());

        $this->assertNotEquals($originalTitle, $ebook->fresh()->title);
        
        $this->assertEquals($originalCover, $ebook->fresh()->cover_path);

        \Storage::disk('public')->assertExists($originalCover);
    }

    /** @test */
    public function an_admin_can_upload_multiple_preview_images()
    {
        $this->signIn();

        $ebook = $this->storeEBook();

        $this->assertCount(0, $ebook->fresh()->previews);

        $this->post(route('admin.ebooks.previews.upload', $ebook), ['preview_image' => UploadedFile::fake()->image('preview1.jpg')]);

        $this->assertCount(1, $ebook->fresh()->previews);

        $this->post(route('admin.ebooks.previews.upload', $ebook), ['preview_image' => UploadedFile::fake()->image('preview2.jpg')]);

        $this->assertCount(2, $ebook->fresh()->previews);
    }

    /** @test */
    public function an_admin_can_remove_previews()
    {
        $this->signIn();

        $ebook = $this->storeEBook();

        $this->assertCount(0, $ebook->fresh()->previews);

        $this->post(route('admin.ebooks.previews.upload', $ebook), ['preview_image' => UploadedFile::fake()->image('preview1.jpg')]);

        $this->assertCount(1, $ebook->fresh()->previews);

        $preview = $ebook->fresh()->previews[0];

        \Storage::disk('public')->assertExists($preview);

        $this->delete(route('admin.ebooks.previews.remove', $ebook), ['preview_path' => $preview]);

        $this->assertCount(0, $ebook->fresh()->previews);

        \Storage::disk('public')->assertMissing($preview);
    }

    /** @test */
    public function an_admin_can_remove_an_ebook()
    {
        $this->signIn();

        $ebook = $this->storeEBook();
        $this->post(route('admin.ebooks.previews.upload', $ebook), ['preview_image' => UploadedFile::fake()->image('preview1.jpg')]);

        $preview = $ebook->fresh()->previews[0];

        $this->delete(route('admin.ebooks.destroy', $ebook));

        $this->assertDatabaseMissing('posts', ['title' => $ebook->title]);

        \Storage::disk('public')->assertMissing($ebook->cover_path);

        \Storage::disk('public')->assertMissing($ebook->pdf_path);

        \Storage::disk('public')->assertMissing($ebook->epub_path);

        \Storage::disk('public')->assertMissing($preview);
    }

    /** @test */
    public function admins_are_notified_when_a_user_purchases_an_ebook()
    {
        \Notification::fake();

        $this->signIn();

        $this->user->purchase($this->ebook);

        \Notification::assertSentTo($this->admin, NewPurchaseCompleted::class);
    }

    /** @test */
    public function only_authorized_users_can_purchase_an_ebook()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');
        
        $this->postStripePurchase($this->ebook->purchaseRoute());         
    }

    /** @test */
    public function an_ebook_purchase_hits_stripe_api_and_charges_the_customer()
    {
    	$this->signIn($this->user);

        $this->postStripePurchase($this->ebook->purchaseRoute());
		
		$purchase = auth()->user()->purchases()->latest()->first();

		$this->assertChargeSucceeded($purchase->charge_id);
    }

    /** @test */
    public function the_same_ebook_can_only_be_purchased_once_by_the_same_customer()
    {
        $this->signIn($this->user);

        $this->postStripePurchase($this->ebook->purchaseRoute());

        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->postStripePurchase($this->ebook->purchaseRoute());
    }

    /** @test */
    public function a_member_can_make_purchases()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();
         
        $this->postStripePurchase($this->ebook->purchaseRoute());
        
        $purchase = auth()->user()->purchases()->latest()->first();

        $this->assertTrue($user->hasMembershipWith('App\Billing\Sources\Stripe'));
        
        $this->assertChargeSucceeded($purchase->charge_id);
    }

    /** @test */
    public function a_customer_can_become_a_member()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripePurchase($this->ebook->purchaseRoute());
        
        $this->postStripeMembership();
        
        $purchase = auth()->user()->purchases()->latest()->first();

        $this->assertTrue($user->hasMembershipWith('App\Billing\Sources\Stripe'));
        
        $this->assertChargeSucceeded($purchase->charge_id);
    }

    /** @test */
    public function admins_are_notified_when_a_purchase_has_been_made()
    {
        \Notification::fake();

        $this->signIn($this->user);

        $this->postStripePurchase($this->ebook->purchaseRoute());

        \Notification::assertSentTo($this->admin, NewPurchaseCompleted::class);
    }

    /** @test */
    public function a_user_receives_an_email_confirming_the_purchase_of_an_ebook_that_also_contains_the_url_to_download_the_product()
    {
        \Mail::fake();

        $this->signIn($this->user);

        $this->postStripePurchase($this->ebook->purchaseRoute());

        \Mail::assertSent(ConfirmPurchase::class);
    }

    /** @test */
    public function a_user_does_not_receive_a_confirmation_email_if_the_product_is_free()
    {
        \Mail::fake();

        $ebook = create(eBook::class, ['price' => 0]);

        $this->signIn($this->user);

        $this->postStripePurchase($ebook->purchaseRoute());

        \Mail::assertNotSent(ConfirmPurchase::class);
    }
}
