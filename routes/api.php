<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// use Illuminate\Http\Request;
// use PhpParser\Node\Expr\FuncCall;
// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\AuthController;

// Route::get('/test', function () {
//     return response()->json([
//         'status' => "done"
//     ]);
// });




// Route::post('/signup', [AuthController::class, 'signup']);
// Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
// Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
// Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
// Route::post('/login', [AuthController::class, 'login']);


// // Route::post('/login', [AuthController::class, 'login']);
// // Route::post('/register', [AuthController::class, 'register']);


// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [AuthController::class, 'logout']);
//     Route::post('/reset-password', [AuthController::class, 'resetPassword']);
// });



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/test', function () {
    return response()->json([
        'status' => "done",
        'message' => 'API is working'
    ]);
});

// Authentication Routes
Route::prefix('auth')->name('auth.')->group(function () {
    // Public routes
    Route::post('/signup', [AuthController::class, 'signup'])->name('signup');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('resend-otp');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
