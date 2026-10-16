<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCardRemovalIdentityToStripeOperations extends Migration
{
    public function up()
    {
        Schema::table('stripe_billing_operations', function (Blueprint $table) {
            $table->string('payment_source_id')->nullable();
            $table->string('card_reference', 64)->nullable();
        });
    }

    public function down()
    {
        Schema::table('stripe_billing_operations', function (Blueprint $table) {
            $table->dropColumn(['payment_source_id', 'card_reference']);
        });
    }
}
