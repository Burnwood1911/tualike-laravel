<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/info', function () {
    return view('info');
});


Route::get('/guest/{eventId}/{guestId}', [EventController::class, 'getCardPage']);
Route::post('/guest/{eventId}/{guestId}/attendance', [EventController::class, 'updateAttendance'])->name('guest.update.attendance');


