<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class AddShopFirstOrderDiscounts extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        Schema::create('shop_first_order_discounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('email_hash', 64);
            $table->string('mode', 12);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->uuid('reserved_order_id')->nullable()->unique();
            $table->uuid('redeemed_order_id')->nullable()->unique();
            $table->timestamps();
            $table->unique(['email_hash', 'mode'], 'shop_first_discount_email_mode');
        });
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->char('checkout_email_hash', 64)->nullable()->index();
            $table->unsignedBigInteger('first_order_discount_id')->nullable()->index();
        });
        // Historical guest purchases count too. Read only the existing encrypted email;
        // never copy an address or associate a guest order with an account.
        DB::table('shop_orders')->select('id', 'recipient')->orderBy('id')->chunk(200, function ($orders) {
            foreach ($orders as $order) {
                $recipient = \Illuminate\Support\Facades\Crypt::decryptString($order->recipient);
                $email = json_decode($recipient, true, 512, JSON_THROW_ON_ERROR)['email'];
                DB::table('shop_orders')->where('id', $order->id)->update([
                    'checkout_email_hash' => hash_hmac('sha256', mb_strtolower(trim($email)), config('app.key')),
                ]);
            }
        });
    }

    public function down()
    {
        $drop = function () {
            Schema::table('shop_orders', function (Blueprint $table) {
                $table->dropIndex(['checkout_email_hash']);
                $table->dropIndex(['first_order_discount_id']);
                $table->dropColumn(['checkout_email_hash', 'first_order_discount_id']);
            });
            Schema::dropIfExists('shop_first_order_discounts');
        };
        if (DB::getDriverName() !== 'sqlite') { $drop(); return; }
        Schema::disableForeignKeyConstraints();
        try { DB::transaction($drop); }
        finally { Schema::enableForeignKeyConstraints(); }
    }
}
