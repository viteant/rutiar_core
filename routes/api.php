<?php
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])
            ->name('auth.me');

        Route::post('logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        Route::post('change-password', [AuthController::class, 'changePassword'])
            ->name('auth.change-password');
    });
});

Route::middleware('auth:sanctum')->get('/tenant-example', function (Request $request) {
    return response()->json([
        'user_id' => $request->user()->id,
        'tenant' => $request->attributes->get('tenant'),
    ]);
})->name('tenant.example');



