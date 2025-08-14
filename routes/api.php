<?php

use App\Models\Employer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\ListCategoryController;
use App\Http\Controllers\TaskPaymentController;
use App\Http\Controllers\FirebaseNotificationController;
use App\Http\Controllers\listCommitController;
use App\Http\Controllers\ListReviewController;

// use App\Http\Controllers\

Route::get('test', function () {
    return response()->json([
        'status' => "done",
        'message' => 'API is working'
    ]);
});




Route::get('/clear-cache', function () {

    Artisan::call('route:clear');
    Artisan::call('route:cache');

    Artisan::call('config:clear');
    Artisan::call('config:cache');

    Artisan::call('view:clear');
    Artisan::call('view:cache');
    Artisan::call('cache:clear');

    return response()->json([
        'status' => 'success',
        'message' => 'All caches cleared and optimized successfully.'
    ]);
});



// Authentication Routes
Route::prefix('auth')->name('auth.')->group(function () {
    // Public routes
    Route::post('/signup', [AuthController::class, 'signup'])->name('signup');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('auth.resend-otp');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    Route::post('/update-profile', [AuthController::class, 'updateProfile'])->name('update_profile');

    // User Profile
    Route::get('/get-user', [AuthController::class, 'getUser'])->name('get_user');

    //Task Route
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{id?}', [TaskController::class, 'update']);
    Route::post('/tasks-delete/{id}', [TaskController::class, 'deleteTask']);
    Route::post('tasks-status', [TaskController::class, 'filterByStatus']);

    //Employer
    Route::get('/get-employer', [EmployerController::class, 'getEmployer']);
    Route::post('/employer/{id}', [EmployerController::class, 'updateEmployer']);
    Route::post('/employ-filter', [EmployerController::class, 'filterByEmployer']);
    Route::post('/delete-employer/{id}', [EmployerController::class, 'deleteEmployer']);

    // Task Payment
    Route::get('get_tasks', [TaskPaymentController::class, 'getTasks']);
    Route::post('task-payment', [TaskPaymentController::class, 'taskPayment']);

    // Earning Route
    Route::get('earningSummary', [TaskPaymentController::class, 'earningSummary']);

    // Fcm notification
    Route::post('fcm-token', [AuthController::class, 'updateFcmToken']);
    Route::get('/fcm-token', [AuthController::class, 'removeFcmToken']);

    // Route::post('/send-reminder', [FirebaeNotificationController::class, 'sendToUser']);
    Route::post('/send-firebase', [FirebaseNotificationController::class, 'sendToUser']);
    Route::post('/send-daily-reminders', [FirebaseNotificationController::class, 'sendDailyReminders']);
    Route::get('/get-reminders-status', [FirebaseNotificationController::class, 'getRemindersStatus']);

    // list
    Route::post('/add-list', [ListController::class, 'addList'])->name('add.list');
    Route::post('/update-list/{id}', [ListController::class, 'updateList'])->name('update.list');
    Route::get('/get-list', [ListController::class, 'getList'])->name('get.list');
    Route::delete('/delete-list/{id}', [ListController::class, 'deleteList'])->name('delete.list');

    //list Category
    Route::get('get-list-category', [ListCategoryController::class, 'getListCategory'])->name('get.list.category');
    Route::post('delete-category/{id}', [ListCategoryController::class, 'deleteCategory'])->name('delete.category');

    //list Commits
    Route::post('/add-list-commits', [listCommitController::class, 'addListCommits'])->name('add.list.commits');
    Route::get('/get-list-commits/{listId}', [listCommitController::class, 'getListCommits'])->name('get.list.commits');

    //list reviews
    Route::post('add-list-review', [ListReviewController::class, 'addListReview'])->name('add.list.review');
    Route::get('/get-list-reviews/{listId}', [ListReviewController::class, 'getListReviews'])->name('get.list.reviews');

    //logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


});


//i have comptete step by step details FCM notification and laravel latest 12 version and new Firsbase setup details expalin
// and integration for reminder throough fcm and used front end for mobile app flutter developer  please explain details step by step
