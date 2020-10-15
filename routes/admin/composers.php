<?php

Route::patch('composers/{composer}/toggle-famous', 'Admin\ComposersController@toggleFamous')->name('composers.toggle-famous');
