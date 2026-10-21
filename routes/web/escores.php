<?php

Route::get('escores/{path?}', 'RedirectsController@shop')->where('path', '.*')->name('legacy.escores');
