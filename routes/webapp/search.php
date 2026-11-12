<?php

Route::namespace('WebApp')->prefix('search')
    ->middleware([\App\Http\Middleware\Search\WebSearchQuery::class, 'search.driver'])
    ->name('search.')->group(function() {
	
	Route::get('', 'SearchController@results')->name('results');

	Route::get('count', 'SearchController@count')->middleware(\App\Http\Middleware\NoIndex::class)->name('count');

});
