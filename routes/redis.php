<?php

Route::post('key/{key}', 'Admin\RedisController@update')->name('update');