<?php

Route::prefix('merchandise')->name('merchandise.')->group(function () {
    Route::get('', 'Admin\MerchandiseController@index')->name('index');
    Route::get('import', 'Admin\MerchandiseController@discover')->name('discover');
    Route::post('import', 'Admin\MerchandiseController@import')->name('import');
    Route::get('{merchandise}/edit', 'Admin\MerchandiseController@edit')->name('edit');
    Route::post('{merchandise}/refresh', 'Admin\MerchandiseController@refresh')->name('refresh');
    Route::post('{merchandise}/images', 'Admin\MerchandiseController@upload')->name('images.upload');
    Route::patch('{merchandise}/images/{image}', 'Admin\MerchandiseController@updateImage')->whereNumber('image')->name('images.update');
    Route::patch('{merchandise}/images/{image}/main', 'Admin\MerchandiseController@main')->whereNumber('image')->name('images.main');
    Route::patch('{merchandise}/images/{image}/move', 'Admin\MerchandiseController@move')->whereNumber('image')->name('images.move');
    Route::delete('{merchandise}/images/{image}', 'Admin\MerchandiseController@remove')->whereNumber('image')->name('images.remove');
    Route::post('{merchandise}/images/{image}/restore', 'Admin\MerchandiseController@restore')->whereNumber('image')->name('images.restore');
});
