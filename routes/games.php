<?php

Route::view('/', 'projects.coming-soon', ['project' => 'games', 'projectTitle' => 'Games'])->name('games.home');
