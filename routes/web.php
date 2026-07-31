<?php

use Illuminate\Support\Facades\Route;

// The landing page, until there is a dashboard to send people to.
Route::get('/', function () {
    return view('welcome');
})->name('home.index');
