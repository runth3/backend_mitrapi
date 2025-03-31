<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('spa'); // Serve the SPA
})->where('any', '.*');
