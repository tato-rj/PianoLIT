<?php

namespace Tests\Feature\Shop;

use Tests\AppTest;
use Tests\Traits\{InteractsWithStripe, AdminEvents};
use App\Shop\eScore;
use App\Mail\Shop\ConfirmPurchase;
use App\Notifications\NewPurchaseCompleted;
use Illuminate\Http\UploadedFile;
use App\User;

class eScoresTest extends AppTest
{
	use InteractsWithStripe, AdminEvents;

    /** @test */
    public function an_admin_can_create_a_new_escore()
    {
        $this->signIn();

        $escore = $this->storeEScore();

        $this->assertDatabaseHas('e_scores', ['title' => $escore->title]);

        $this->assertNotNull($escore->cover_path);

        $this->assertNotNull($escore->audio_path);

        \Storage::disk('public')->assertExists($escore->cover_path);

        \Storage::disk('public')->assertExists($escore->audio_path);
    }

    /** @test */
    public function an_admin_cannot_create_two_escores_with_the_same_name()
    {
        $this->expectException('Illuminate\Validation\ValidationException');
        
        $this->signIn();

        $escore1 = $this->storeEScore();

        $escore2 = make(eScore::class, ['title' => $escore1->title]);

        $this->post(route('admin.escores.store'), $escore2->toArray());
    }

    /** @test */
    public function an_admin_can_edit_an_existing_escore()
    {
        $this->signIn();

        $escore = $this->storeEScore();

        $originalTitle = $escore->title;
        $originalCover = $escore->cover_path;
        $originalPDF = $escore->pdf_path;
        $originalAudio = $escore->audio_path;

        $update = make(eScore::class, [
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'pdf_file' => UploadedFile::fake()->image('new-pdf.pdf'),
            'audio_file' => UploadedFile::fake()->image('new-audio.mp4')
        ]);

        $this->patch(route('admin.escores.update', $escore), $update->toArray());

        $this->assertNotEquals($originalTitle, $escore->fresh()->title);

        $this->assertNotEquals($originalCover, $escore->fresh()->cover_path);

        $this->assertNotEquals($originalPDF, $escore->fresh()->pdf_path);

        $this->assertNotEquals($originalAudio, $escore->fresh()->audio_path);

        \Storage::disk('public')->assertMissing($originalCover);

        \Storage::disk('public')->assertMissing($originalAudio);

        \Storage::disk('public')->assertMissing($originalPDF);
        
        \Storage::disk('public')->assertExists($escore->fresh()->cover_path);

        \Storage::disk('public')->assertExists($escore->fresh()->audio_path);

        \Storage::disk('public')->assertExists($escore->fresh()->pdf_path);
    }

    /** @test */
    public function an_audio_file_is_not_updated_if_not_present_on_the_update_request()
    {
        $this->signIn();

        $escore = $this->storeEScore();

        $originalAudio = $escore->audio_path;

        $update = make(eScore::class, ['audio_file' => null]);

        $this->patch(route('admin.escores.update', $escore), $update->toArray());

        $this->assertEquals($originalAudio, $escore->fresh()->audio_path);

        $this->assertNotNull($escore->fresh()->audio_path);
        
        \Storage::disk('public')->assertExists($escore->fresh()->audio_path);

        \Storage::disk('public')->assertExists($originalAudio);
    }

    /** @test */
    public function only_authorized_users_can_purchase_an_escore()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');
        
        $this->postStripePurchase($this->escore->purchaseRoute());         
    }

    /** @test */
    public function an_escore_purchase_hits_stripe_api_and_charges_the_customer()
    {
    	$this->signIn($this->user);

        $this->postStripePurchase($this->ebook->purchaseRoute());

        $this->postStripePurchase($this->escore->purchaseRoute());
		
		$purchase = auth()->user()->purchases()->latest()->first();

		$this->assertChargeSucceeded($purchase->charge_id);
    }

    /** @test */
    public function the_same_escore_can_only_be_purchased_once_by_the_same_customer()
    {
        $this->signIn($this->user);

        $this->postStripePurchase($this->escore->purchaseRoute());

        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->postStripePurchase($this->escore->purchaseRoute());
    }

    /** @test */
    public function an_admin_can_remove_an_escore()
    {
        $this->signIn();

        $escore = $this->storeEScore();

        $this->post(route('admin.escores.previews.upload', $escore), ['preview_image' => UploadedFile::fake()->image('preview1.jpg')]);

        $preview = $escore->fresh()->previews[0];

        $this->delete(route('admin.escores.destroy', $escore));

        $this->assertDatabaseMissing('posts', ['title' => $escore->title]);

        \Storage::disk('public')->assertMissing($escore->cover_path);

        \Storage::disk('public')->assertMissing($escore->pdf_path);

        \Storage::disk('public')->assertMissing($escore->audio_path);

        \Storage::disk('public')->assertMissing($preview);
    }

    /** @test */
    public function a_member_can_make_purchases()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();
         
        $this->postStripePurchase($this->escore->purchaseRoute());
        
        $purchase = auth()->user()->purchases()->latest()->first();

        $this->assertTrue($user->hasMembershipWith('App\Billing\Sources\Stripe'));
        
        $this->assertChargeSucceeded($purchase->charge_id);
    }

    /** @test */
    public function a_customer_can_become_a_member()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripePurchase($this->escore->purchaseRoute());
        
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

        $this->postStripePurchase($this->escore->purchaseRoute());

        \Notification::assertSentTo($this->admin, NewPurchaseCompleted::class);
    }

    /** @test */
    public function a_user_receives_an_email_confirming_the_purchase_of_an_escore_that_also_contains_the_url_to_download_the_product()
    {
        \Mail::fake();

        $this->signIn($this->user);

        $this->postStripePurchase($this->escore->purchaseRoute());

        \Mail::assertSent(ConfirmPurchase::class);
    }

    /** @test */
    public function a_user_does_not_receive_a_confirmation_email_if_the_product_is_free()
    {
        \Mail::fake();

        $escore = create(eScore::class, ['price' => 0]);

        $this->signIn($this->user);

        $this->postStripePurchase($escore->purchaseRoute());

        \Mail::assertNotSent(ConfirmPurchase::class);
    }
}
