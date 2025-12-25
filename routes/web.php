<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HashTagController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('auth.login');
});

Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'analytics'])->name('analytics');
    });
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'list'])->name('list');
        Route::get('/ajax-get-data', [CategoryController::class, 'ajaxGetData'])->name('ajaxGetData');
        Route::get('/ajax-get-trashed-data', [CategoryController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData');
        Route::get('/ajax-get-tree-view/{type}', [CategoryController::class, 'ajaxGetTreeView'])->name('ajax-get-tree-view');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::get('/ajax-get-category-by-type', [CategoryController::class, 'ajaxGetCategoryByType'])->name('ajax-get-category-by-type');
        Route::post('/store', [CategoryController::class, 'store'])->name('store');
        Route::post('/update-order', [CategoryController::class, 'updateOrder'])->name('updateOrder');
        Route::delete('/destroy/{id}', [CategoryController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        Route::post('/restore/{id}', [CategoryController::class, 'restore'])->name('restore')->where('id', '[0-9]+');
        Route::delete('/force-delete/{id}', [CategoryController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+');
        Route::delete('/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('bulkDelete');
        Route::post('/bulk-restore', [CategoryController::class, 'bulkRestore'])->name('bulkRestore');
        Route::delete('/bulk-force-delete', [CategoryController::class, 'bulkForceDelete'])->name('bulkForceDelete');
        Route::get('/delete-info/{id}', [CategoryController::class, 'getDeleteInfo'])->name('deleteInfo')->where('id', '[0-9]+');
        Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
        Route::put('/update/{id}', [CategoryController::class, 'update'])->name('update')->where('id', '[0-9]+');
        Route::post('/quick-store', [CategoryController::class, 'quickStore'])->name('quickStore');
    });
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/', [PostController::class, 'list'])->name('list');
        Route::get('/ajax-get-data', [PostController::class, 'ajaxGetData'])->name('ajaxGetData');
        Route::get('/ajax-get-trashed-data', [PostController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData');
        Route::get('/create', [PostController::class, 'create'])->name('create');
        Route::post('/store', [PostController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [PostController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
        Route::put('/update/{id}', [PostController::class, 'update'])->name('update')->where('id', '[0-9]+');
        Route::delete('/destroy/{id}', [PostController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        Route::post('/restore/{id}', [PostController::class, 'restore'])->name('restore')->where('id', '[0-9]+');
        Route::delete('/force-delete/{id}', [PostController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+');
        Route::delete('/bulk-delete', [PostController::class, 'bulkDelete'])->name('bulkDelete');
        Route::post('/bulk-restore', [PostController::class, 'bulkRestore'])->name('bulkRestore');
        Route::delete('/bulk-force-delete', [PostController::class, 'bulkForceDelete'])->name('bulkForceDelete');
        Route::post('/bulk-move-category', [PostController::class, 'bulkMoveCategory'])->name('bulkMoveCategory');
        Route::get('/{id}/publish', [PostController::class, 'publish'])->name('publish')->where('id', '[0-9]+');
        Route::get('/{id}/views', [PostController::class, 'getPostViews'])->name('views')->where('id', '[0-9]+');
    });
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [ContactController::class, 'list'])->name('list');
        Route::get('/ajax-get-data', [ContactController::class, 'ajaxGetData'])->name('ajaxGetData');
        Route::get('/count-pending', [ContactController::class, 'countPending'])->name('countPending');
        Route::get('/{id}', [ContactController::class, 'show'])->name('show')->where('id', '[0-9]+');
        Route::post('/{id}/reply', [ContactController::class, 'reply'])->name('reply')->where('id', '[0-9]+');
        Route::put('/change-status/{id}/{status}', [ContactController::class, 'changeStatus'])
            ->where(['id' => '[0-9]+', 'status' => '[0-3]'])
            ->name('changeStatus');
    });
    Route::prefix('hashtags')->name('hashtags.')->group(function () {
        Route::get('/', [HashTagController::class, 'list'])->name('list');
        Route::get('/ajax-get-data', [HashTagController::class, 'ajaxGetData'])->name('ajaxGetData');
        Route::get('/ajax-get-trashed-data', [HashTagController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData');
        Route::get('/create', [HashTagController::class, 'create'])->name('create');
        Route::post('/store', [HashTagController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [HashTagController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
        Route::put('/update/{id}', [HashTagController::class, 'update'])->name('update')->where('id', '[0-9]+');
        Route::delete('/destroy/{id}', [HashTagController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        Route::post('/restore/{id}', [HashTagController::class, 'restore'])->name('restore')->where('id', '[0-9]+');
        Route::delete('/force-delete/{id}', [HashTagController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+');
        Route::delete('/bulk-delete', [HashTagController::class, 'bulkDelete'])->name('bulkDelete');
        Route::post('/bulk-restore', [HashTagController::class, 'bulkRestore'])->name('bulkRestore');
        Route::delete('/bulk-force-delete', [HashTagController::class, 'bulkForceDelete'])->name('bulkForceDelete');
        Route::get('/search', [HashTagController::class, 'search'])->name('search');
        Route::post('/quick-store', [HashTagController::class, 'quickStore'])->name('quickStore');
    });

    Route::prefix('users')->name('users.')->group(function () {
        // Quản lý người dùng
        Route::get('/', [UserController::class, 'list'])->name('list');
        Route::get('/ajax-get-data', [UserController::class, 'ajaxGetData'])->name('ajaxGetData');
        Route::get('/ajax-get-trashed-data', [UserController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/store', [UserController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [UserController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
        Route::put('/update/{id}', [UserController::class, 'update'])->name('update')->where('id', '[0-9]+');
        Route::delete('/destroy/{id}', [UserController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
        Route::post('/restore/{id}', [UserController::class, 'restore'])->name('restore')->where('id', '[0-9]+');
        Route::delete('/force-delete/{id}', [UserController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+');

        // Bulk actions
        Route::post('/bulk-delete', [UserController::class, 'bulkDelete'])->name('bulkDelete');
        Route::post('/bulk-restore', [UserController::class, 'bulkRestore'])->name('bulkRestore');
        Route::post('/bulk-force-delete', [UserController::class, 'bulkForceDelete'])->name('bulkForceDelete');

        // Trang cá nhân & đổi mật khẩu
        Route::get('/profile', [ProfileController::class, 'profile'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('updateProfile');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('changePassword');
    });
    Route::get('media', function () {
        return view('admin.modules.media.index');
    })->name('media');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
        Route::get('/test-email-setup', [SettingController::class, 'testEmailSetup'])->name('testEmailSetup');
    });
});
// Authentication routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/loginHandle', [AuthController::class, 'loginHandle'])->name('loginHandle');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('forgot-password');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])->name('forgot-password.send');
    Route::get('/reset-password', [AuthController::class, 'showResetPasswordForm'])->name('reset-password');
    Route::post('/reset-password', [AuthController::class, 'updatePassword'])->name('reset-password.update');
});

Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});
