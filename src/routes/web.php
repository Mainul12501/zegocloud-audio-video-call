<?php

use Illuminate\Support\Facades\Route;
use ZegoAudioVideoCalling\Controllers\ZegoCloudController;

if (config('zego-calling.routes.web.enabled', true)) {
    $prefix = config('zego-calling.routes.web.prefix', 'call');
    $middleware = config('zego-calling.routes.web.middleware', ['web', 'auth']);

    Route::prefix($prefix)
        ->name('zego-calling.')
        ->middleware($middleware)
        ->group(function () {
            Route::get('/call-page', [ZegoCloudController::class, 'viewCallPage'])->name('call-page');
            Route::post('/initiate', [ZegoCloudController::class, 'initiateCall'])->name('initiate');
            Route::post('/{call}/accept', [ZegoCloudController::class, 'acceptCall'])->name('accept');
            Route::post('/{call}/reject', [ZegoCloudController::class, 'rejectCall'])->name('reject');
            Route::post('/{call}/end', [ZegoCloudController::class, 'endCall'])->name('end');
            Route::get('/{call}/details', [ZegoCloudController::class, 'getCallDetails'])->name('details');
            Route::post('/generate-token', [ZegoCloudController::class, 'generateToken'])->name('generate-token');
        });
}
