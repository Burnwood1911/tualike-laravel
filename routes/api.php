<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('cards/get-cards', [CardController::class, 'getCards']);
Route::get('cards/get-card/{id}', [CardController::class, 'getCard']);

Route::get('cards/getCardCategories', [CardController::class, 'getCardCategories']);

Route::get('event/getGuests/{id}', [EventController::class, 'getGuests']);


Route::post('event/scan', [EventController::class, 'scan']);

Route::post('orders', [OrderController::class, 'store']);
