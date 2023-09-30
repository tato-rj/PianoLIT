<?php

Route::prefix('qr-code')->name('qrcode.')->group(function() {

	Route::get('', 'Admin\QrcodeController@download')->name('download');

});
