<?php

use App\Http\Controllers\SpotifyController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('home');

// Route::get('dashboard', function () {
//     return Inertia::render('Welcome');
// })->name('dashboard');



Route::get('spotify', function () {
    return Inertia::render('Spotify');
})->name('spotify');

Route::post('spotify/search', [SpotifyController::class, 'search'])->name('spotify.search');
Route::post('spotify/playlist/add', [SpotifyController::class, 'addToPlaylist'])->name('spotify.playlist.add');
Route::get('spotify/callback', [SpotifyController::class, 'callback'])->name('spotify.callback');
Route::get('spotify/callback', [SpotifyController::class, 'callback'])->name('spotify.callback');
