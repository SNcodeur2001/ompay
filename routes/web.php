<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'OM Pay API is running successfully!',
        'status' => 'OK',
        'version' => '1.0.0',
        'documentation' => url('/api/documentation'),
        'endpoints' => [
            'auth' => [
                'register' => url('/api/auth/register'),
                'login' => url('/api/auth/login'),
                'verify_otp' => url('/api/auth/verify-otp'),
                'logout' => url('/api/auth/logout'),
            ],
            'accounts' => [
                'list' => url('/api/comptes'),
                'create' => url('/api/comptes'),
            ],
            'transactions' => [
                'list' => url('/api/transactions'),
                'create' => url('/api/transactions'),
            ]
        ]
    ]);
});

Route::get('/login', function () {
    return response()->json(['message' => 'Please use /api/auth/login for authentication']);
})->name('login');
