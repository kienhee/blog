<?php

use App\Http\Controllers\Client\AuthController;
use App\Http\Controllers\Client\CategoryController;
use App\Http\Controllers\Client\CommentController;
use App\Http\Controllers\Client\ContactController;
use App\Http\Controllers\Client\HashtagController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\PageController;
use App\Http\Controllers\Client\PostController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\SavedPostController;
use Illuminate\Support\Facades\Route;

// Client
Route::prefix('/')->name('client.')->group(function () {
    // Home & Search
    Route::get('/', [HomeController::class, 'home'])->name('home');
    Route::get('/tim-kiem', [HomeController::class, 'search'])->name('search');
    Route::get('/bai-viet', [HomeController::class, 'posts'])->name('posts');

    // Posts
    Route::get('/bai-viet/{slug}', [PostController::class, 'post'])->name('post');
    Route::get('/api/posts-by-category', [PostController::class, 'getPostsByCategory'])->name('api.posts-by-category');

    // Categories
    Route::get('/danh-muc/{slug}', [CategoryController::class, 'category'])->name('category');

    // Hashtags
    Route::get('/tag/{slug}', [HashtagController::class, 'hashtag'])->name('hashtag');

    // Contact
    Route::get('/lien-he', [ContactController::class, 'contact'])->name('contact');
    Route::post('/lien-he', [ContactController::class, 'submitContact'])->name('contact.submit');

    // Pages
    Route::get('/tac-gia', [PageController::class, 'about'])->name('about');
    Route::post('/newsletter/subscribe', [PageController::class, 'subscribeNewsletter'])->name('newsletter.subscribe');

    // Client Auth Routes
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/dang-nhap', [AuthController::class, 'login'])->name('login');
        Route::post('/dang-nhap', [AuthController::class, 'loginHandle'])->name('loginHandle');
        Route::get('/dang-ky', [AuthController::class, 'register'])->name('register');
        Route::post('/dang-ky', [AuthController::class, 'registerHandle'])->name('registerHandle');
        Route::post('/dang-xuat', [AuthController::class, 'logout'])->name('logout');
        Route::get('/quen-mat-khau', [AuthController::class, 'showForgotPasswordForm'])->name('forgot-password');
        Route::post('/quen-mat-khau', [AuthController::class, 'sendPasswordResetLink'])->name('forgot-password.send');
        Route::get('/dat-lai-mat-khau', [AuthController::class, 'showResetPasswordForm'])->name('reset-password');
        Route::post('/dat-lai-mat-khau', [AuthController::class, 'updatePassword'])->name('reset-password.update');
    });

    // Client Profile Routes (require auth)
    Route::middleware('auth')->group(function () {
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/thong-tin-ca-nhan', [ProfileController::class, 'information'])->name('information');
            Route::put('/thong-tin-ca-nhan', [ProfileController::class, 'updateInformation'])->name('updateInformation');
            Route::get('/bai-viet-da-luu', [ProfileController::class, 'savedPosts'])->name('savedPosts');
            Route::get('/doi-mat-khau', [ProfileController::class, 'showChangePassword'])->name('changePassword');
            Route::post('/doi-mat-khau', [ProfileController::class, 'changePassword'])->name('changePassword.post');
        });

        // Saved Posts
        Route::prefix('saved-posts')->name('saved-posts.')->group(function () {
            Route::post('/{postId}/toggle', [SavedPostController::class, 'toggle'])->name('toggle');
        });

        // Comments (require auth)
        Route::prefix('comments')->name('comments.')->group(function () {
            Route::post('/', [CommentController::class, 'store'])->name('store');
        });
    });

    // Saved posts check route (accessible even when not logged in)
    Route::get('/saved-posts/{postId}/check', [SavedPostController::class, 'check'])->name('saved-posts.check');
});
