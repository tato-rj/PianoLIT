<?php

Route::get('ebooks/{path?}', 'RedirectsController@shop')->where('path', '.*')->name('legacy.ebooks');
