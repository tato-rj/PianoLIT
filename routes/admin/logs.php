<?php

Route::get('logs/data', 'Admin\LogsController@data')->name('log.data');

Route::get('memberships/logs', 'MembershipsController@logs')->name('memberships.logs');