<?php

Route::group(['namespace' => 'Webhooks'], function () {

    Route::post('mailgun', 'MailgunWebhookController')->name('mailgun');

    Route::post('stripe', 'StripeWebhookController')->name('stripe');

    Route::post('file-manager', 'FileManagerWebhookController')->name('file-manager');

    Route::delete('file-manager', 'FileManagerWebhookController');

});