<?php

use Illuminate\Support\Facades\Route;
use ZegoAudioVideoCalling\Controllers\MobileApiController;
use ZegoAudioVideoCalling\Controllers\ZegoCloudController;

// Web API Routes
if (config('zego-calling.routes.api.enabled', true)) {
    $prefix = config('zego-calling.routes.api.prefix', 'api/call');
    $middleware = config('zego-calling.routes.api.middleware', ['api', 'auth:sanctum']);

    Route::prefix($prefix)
        ->name('zego-calling.api.')
        ->middleware($middleware)
        ->group(function () {
            Route::post('/initiate', [ZegoCloudController::class, 'initiateCall']);
            Route::post('/{call}/accept', [ZegoCloudController::class, 'acceptCall']);
            Route::post('/{call}/reject', [ZegoCloudController::class, 'rejectCall']);
            Route::post('/{call}/end', [ZegoCloudController::class, 'endCall']);
            Route::get('/{call}/details', [ZegoCloudController::class, 'getCallDetails']);
            Route::post('/generate-token', [ZegoCloudController::class, 'generateToken']);
        });
}

// Mobile API Routes
if (config('zego-calling.routes.mobile.enabled', true)) {
    $prefix = config('zego-calling.routes.mobile.prefix', 'api/mobile/call');
    $middleware = config('zego-calling.routes.mobile.middleware', ['api', 'auth:sanctum']);

    Route::prefix($prefix)
        ->name('zego-calling.mobile.')
        ->middleware($middleware)
        ->group(function () {
            // Device registration
            Route::post('/register-device', [MobileApiController::class, 'registerDevice']);
            Route::post('/update-online-status', [MobileApiController::class, 'updateOnlineStatus']);

            // Call management
            Route::post('/initiate', [MobileApiController::class, 'initiateCall']);
            Route::post('/{callId}/accept', [MobileApiController::class, 'acceptCall']);
            Route::post('/{callId}/reject', [MobileApiController::class, 'rejectCall']);
            Route::post('/{callId}/end', [MobileApiController::class, 'endCall']);

            // Call information
            Route::get('/active-calls', [MobileApiController::class, 'getActiveCalls']);
            Route::get('/call-history', [MobileApiController::class, 'getCallHistory']);
            Route::get('/{callId}/details', [MobileApiController::class, 'getCallDetails']);
            Route::get('/user/{userId}/availability', [MobileApiController::class, 'checkUserAvailability']);

            // ZegoCloud configuration
            Route::post('/generate-token', [MobileApiController::class, 'generateToken']);
        });
}
