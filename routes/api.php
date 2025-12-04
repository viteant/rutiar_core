<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompanyConfigController;
use App\Http\Controllers\CompanyPermissionController;
use App\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');

    });
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('company/config', [CompanyConfigController::class, 'show']);
    Route::put('company/config', [CompanyConfigController::class, 'update']);

    Route::prefix('company/permissions')->group(function () {
        Route::get('available', [CompanyPermissionController::class, 'availablePermissions']);
        Route::get('roles', [CompanyPermissionController::class, 'listRolePermissions']);
        Route::put('roles/{role}', [CompanyPermissionController::class, 'updateRolePermissions']);

        Route::get('users/{userId}', [CompanyPermissionController::class, 'showUserPermissions']);
        Route::put('users/{userId}', [CompanyPermissionController::class, 'updateUserPermissions']);
    });

    Route::apiResource('partners', PartnerController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::get('/tenant-example', function (Request $request) {
        return response()->json([
            'user_id' => $request->user()->id,
            'tenant' => $request->attributes->get('tenant'),
        ]);
    })->name('tenant.example');

});



