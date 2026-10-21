<?php

Route::get('blog/{path?}', 'RedirectsController@blog')->where('path', '.*')->name('legacy.blog');
