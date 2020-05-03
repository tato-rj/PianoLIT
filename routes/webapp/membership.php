<?php

Route::namespace('WebApp')->prefix('membership')->name('membership.')->group(function() {

	Route::get('pricing', 'MembershipsController@pricing')->name('pricing');

});