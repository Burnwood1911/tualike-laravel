<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy', function () {
    return view('privacy');
});
Route::get('/terms', function () {
    return view('terms');
});

Route::get('/info', function () {
    return view('info');
});

Route::get('/guest/{eventId}/{guestId}', [EventController::class, 'getCardPage']);
Route::post('/guest/{eventId}/{guestId}/attendance', [EventController::class, 'updateAttendance'])->name('guest.update.attendance');

Route::get('/test-r2', [App\Http\Controllers\TestR2Controller::class, 'testConnection']);

Route::get('/export/guest/{key}', function ($key) {
    // Get export metadata from cache
    $metadata = \Cache::get("export_meta_{$key}");
    
    if (!$metadata) {
        abort(404, 'Export not found or expired');
    }
    
    // Check if file exists
    if (!\Storage::exists($metadata['path'])) {
        abort(404, 'Export file not found');
    }
    
    // Get file content
    $content = \Storage::get($metadata['path']);
    
    // Clean up after download
    \Storage::delete($metadata['path']);
    \Cache::forget("export_meta_{$key}");
    
    // Return file download
    return response($content)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', 'attachment; filename="' . $metadata['filename'] . '"');
})->name('guest.export.download');
