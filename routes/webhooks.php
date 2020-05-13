<?php

Route::group(['namespace' => 'Webhooks'], function () {

    Route::post('mailgun', 'MailgunWebhookController')->name('mailgun');

    Route::post('stripe', 'StripeWebhookController')->name('stripe');

});