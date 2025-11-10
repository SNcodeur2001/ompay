<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use Laravel\Passport\Http\Controllers\PersonalAccessTokenController;
use Laravel\Passport\Http\Controllers\TransientTokenController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompteController;
use App\Http\Controllers\Api\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('set-pin', [AuthController::class, 'setPin']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-pin', [AuthController::class, 'changePin']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Compte routes
    Route::get('compte', [CompteController::class, 'show']);
    Route::post('compte/depot', [CompteController::class, 'depot']);

    // Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::post('paiement', [TransactionController::class, 'paiement']);
        Route::post('transfert', [TransactionController::class, 'transfert']);
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('{reference}', [TransactionController::class, 'show']);
    });
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-pin', [AuthController::class, 'changePin']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Compte routes
    Route::get('compte', [CompteController::class, 'show']);
    Route::post('compte/depot', [CompteController::class, 'depot']);

    // Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::post('paiement', [TransactionController::class, 'paiement']);
        Route::post('transfert', [TransactionController::class, 'transfert']);
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('{reference}', [TransactionController::class, 'show']);
    });
});




 




























































Route::prefix('oauth')->group(function () {
    Route::post('/token', [AccessTokenController::class, 'issueToken'])
        ->middleware(['throttle:60,1'])
        ->name('passport.token');

    Route::get('/authorize', [AuthorizationController::class, 'authorize'])
        ->name('passport.authorizations.authorize');

    Route::post('/authorize', [ApproveAuthorizationController::class, 'approve'])
        ->name('passport.authorizations.approve');

    Route::delete('/authorize', [DenyAuthorizationController::class, 'deny'])
        ->name('passport.authorizations.deny');

    Route::post('/personal-access-tokens', [PersonalAccessTokenController::class, 'store'])
        ->name('passport.personal.tokens');

    Route::get('/token/refresh', [TransientTokenController::class, 'refresh'])
        ->middleware('auth:api')
        ->name('passport.token.refresh');
});
