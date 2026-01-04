<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HashTagController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\FinanceMonthController;
use App\Http\Controllers\Admin\FinanceYearController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

include 'client.php';
// Admin
Route::prefix('admin')->middleware(['auth', 'prevent.guest.admin'])->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard.analytics');
    })->name('index');
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'analytics'])->name('analytics');
    });
    Route::prefix('categories')->name('categories.')->group(function () {
        // Read permissions
        Route::get('/', [CategoryController::class, 'list'])->name('list')->middleware('permission:category.read');
        Route::get('/ajax-get-data', [CategoryController::class, 'ajaxGetData'])->name('ajaxGetData')->middleware('permission:category.read');
        Route::get('/ajax-get-trashed-data', [CategoryController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData')->middleware('permission:category.read');
        Route::get('/ajax-get-tree-view/{type}', [CategoryController::class, 'ajaxGetTreeView'])->name('ajax-get-tree-view')->middleware('permission:category.read');
        Route::get('/ajax-get-category-by-type', [CategoryController::class, 'ajaxGetCategoryByType'])->name('ajax-get-category-by-type')->middleware('permission:category.read');
        Route::get('/delete-info/{id}', [CategoryController::class, 'getDeleteInfo'])->name('deleteInfo')->where('id', '[0-9]+')->middleware('permission:category.read');
        Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('edit')->where('id', '[0-9]+')->middleware('permission:category.read');

        // Create permissions
        Route::get('/create', [CategoryController::class, 'create'])->name('create')->middleware('permission:category.create');
        Route::post('/store', [CategoryController::class, 'store'])->name('store')->middleware('permission:category.create');
        Route::post('/quick-store', [CategoryController::class, 'quickStore'])->name('quickStore')->middleware('permission:category.create');

        // Update permissions
        Route::put('/update/{id}', [CategoryController::class, 'update'])->name('update')->where('id', '[0-9]+')->middleware('permission:category.update');
        Route::post('/update-order', [CategoryController::class, 'updateOrder'])->name('updateOrder')->middleware('permission:category.update');
        Route::post('/restore/{id}', [CategoryController::class, 'restore'])->name('restore')->where('id', '[0-9]+')->middleware('permission:category.update');
        Route::post('/bulk-restore', [CategoryController::class, 'bulkRestore'])->name('bulkRestore')->middleware('permission:category.update');

        // Delete permissions
        Route::delete('/destroy/{id}', [CategoryController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+')->middleware('permission:category.delete');
        Route::delete('/force-delete/{id}', [CategoryController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+')->middleware('permission:category.delete');
        Route::delete('/bulk-delete', [CategoryController::class, 'bulkDelete'])->name('bulkDelete')->middleware('permission:category.delete');
        Route::delete('/bulk-force-delete', [CategoryController::class, 'bulkForceDelete'])->name('bulkForceDelete')->middleware('permission:category.delete');
    });
    Route::prefix('posts')->name('posts.')->group(function () {
        // Read permissions
        Route::get('/', [PostController::class, 'list'])->name('list')->middleware('permission:post.read');
        Route::get('/ajax-get-data', [PostController::class, 'ajaxGetData'])->name('ajaxGetData')->middleware('permission:post.read');
        Route::get('/ajax-get-trashed-data', [PostController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData')->middleware('permission:post.read');
        Route::get('/edit/{id}', [PostController::class, 'edit'])->name('edit')->where('id', '[0-9]+')->middleware('permission:post.read');
        Route::get('/{id}/views', [PostController::class, 'getPostViews'])->name('views')->where('id', '[0-9]+')->middleware('permission:post.read');

        // Create permissions
        Route::get('/create', [PostController::class, 'create'])->name('create')->middleware('permission:post.create');
        Route::post('/store', [PostController::class, 'store'])->name('store')->middleware('permission:post.create');

        // Update permissions
        Route::put('/update/{id}', [PostController::class, 'update'])->name('update')->where('id', '[0-9]+')->middleware('permission:post.update');
        Route::post('/restore/{id}', [PostController::class, 'restore'])->name('restore')->where('id', '[0-9]+')->middleware('permission:post.update');
        Route::post('/bulk-restore', [PostController::class, 'bulkRestore'])->name('bulkRestore')->middleware('permission:post.update');
        Route::post('/bulk-move-category', [PostController::class, 'bulkMoveCategory'])->name('bulkMoveCategory')->middleware('permission:post.update');
        Route::get('/{id}/publish', [PostController::class, 'publish'])->name('publish')->where('id', '[0-9]+')->middleware('permission:post.update');

        // Delete permissions
        Route::delete('/destroy/{id}', [PostController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+')->middleware('permission:post.delete');
        Route::delete('/force-delete/{id}', [PostController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+')->middleware('permission:post.delete');
        Route::delete('/bulk-delete', [PostController::class, 'bulkDelete'])->name('bulkDelete')->middleware('permission:post.delete');
        Route::delete('/bulk-force-delete', [PostController::class, 'bulkForceDelete'])->name('bulkForceDelete')->middleware('permission:post.delete');
    });
    Route::prefix('contacts')->name('contacts.')->group(function () {
        // Read permissions
        Route::get('/', [ContactController::class, 'list'])->name('list')->middleware('permission:contact.read');
        Route::get('/ajax-get-data', [ContactController::class, 'ajaxGetData'])->name('ajaxGetData')->middleware('permission:contact.read');
        Route::get('/count-pending', [ContactController::class, 'countPending'])->name('countPending')->middleware('permission:contact.read');
        Route::get('/{id}', [ContactController::class, 'show'])->name('show')->where('id', '[0-9]+')->middleware('permission:contact.read');

        // Update permissions
        Route::post('/{id}/reply', [ContactController::class, 'reply'])->name('reply')->where('id', '[0-9]+')->middleware('permission:contact.update');
        Route::put('/change-status/{id}/{status}', [ContactController::class, 'changeStatus'])
            ->where(['id' => '[0-9]+', 'status' => '[0-3]'])
            ->name('changeStatus')
            ->middleware('permission:contact.update');
    });
    Route::prefix('newsletters')->name('newsletters.')->group(function () {
        // Read permissions
        Route::get('/', [NewsletterController::class, 'list'])->name('list')->middleware('permission:newsletter.read');
        Route::get('/ajax-get-data', [NewsletterController::class, 'ajaxGetData'])->name('ajaxGetData')->middleware('permission:newsletter.read');

        // Delete permissions
        Route::delete('/destroy/{id}', [NewsletterController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+')->middleware('permission:newsletter.delete');
    });
    Route::prefix('comments')->name('comments.')->group(function () {
        // Read permissions
        Route::get('/', [CommentController::class, 'list'])->name('list')->middleware('permission:comment.read');
        Route::get('/ajax-get-data', [CommentController::class, 'ajaxGetData'])->name('ajaxGetData')->middleware('permission:comment.read');
        Route::get('/ajax-get-trashed-data', [CommentController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData')->middleware('permission:comment.read');
        Route::get('/count-pending', [CommentController::class, 'countPending'])->name('countPending')->middleware('permission:comment.read');
        Route::get('/{id}', [CommentController::class, 'show'])->name('show')->where('id', '[0-9]+')->middleware('permission:comment.read');

        // Update permissions
        Route::post('/{id}/reply', [CommentController::class, 'reply'])->name('reply')->where('id', '[0-9]+')->middleware('permission:comment.update');
        Route::put('/change-status/{id}/{status}', [CommentController::class, 'changeStatus'])
            ->where(['id' => '[0-9]+', 'status' => '[a-z]+'])
            ->name('changeStatus')
            ->middleware('permission:comment.update');
        Route::post('/bulk-change-status', [CommentController::class, 'bulkChangeStatus'])->name('bulkChangeStatus')->middleware('permission:comment.update');
        Route::post('/restore/{id}', [CommentController::class, 'restore'])->name('restore')->where('id', '[0-9]+')->middleware('permission:comment.update');
        Route::post('/bulk-restore', [CommentController::class, 'bulkRestore'])->name('bulkRestore')->middleware('permission:comment.update');

        // Delete permissions
        Route::delete('/destroy/{id}', [CommentController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+')->middleware('permission:comment.delete');
        Route::delete('/bulk-delete', [CommentController::class, 'bulkDelete'])->name('bulkDelete')->middleware('permission:comment.delete');
        Route::delete('/force-delete/{id}', [CommentController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+')->middleware('permission:comment.delete');
        Route::delete('/bulk-force-delete', [CommentController::class, 'bulkForceDelete'])->name('bulkForceDelete')->middleware('permission:comment.delete');
    });
    Route::prefix('hashtags')->name('hashtags.')->group(function () {
        // Read permissions
        Route::get('/', [HashTagController::class, 'list'])->name('list')->middleware('permission:hashtag.read');
        Route::get('/ajax-get-data', [HashTagController::class, 'ajaxGetData'])->name('ajaxGetData')->middleware('permission:hashtag.read');
        Route::get('/ajax-get-trashed-data', [HashTagController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData')->middleware('permission:hashtag.read');
        Route::get('/edit/{id}', [HashTagController::class, 'edit'])->name('edit')->where('id', '[0-9]+')->middleware('permission:hashtag.read');
        Route::get('/search', [HashTagController::class, 'search'])->name('search')->middleware('permission:hashtag.read');

        // Create permissions
        Route::get('/create', [HashTagController::class, 'create'])->name('create')->middleware('permission:hashtag.create');
        Route::post('/store', [HashTagController::class, 'store'])->name('store')->middleware('permission:hashtag.create');
        Route::post('/quick-store', [HashTagController::class, 'quickStore'])->name('quickStore')->middleware('permission:hashtag.create');

        // Update permissions
        Route::put('/update/{id}', [HashTagController::class, 'update'])->name('update')->where('id', '[0-9]+')->middleware('permission:hashtag.update');
        Route::post('/restore/{id}', [HashTagController::class, 'restore'])->name('restore')->where('id', '[0-9]+')->middleware('permission:hashtag.update');
        Route::post('/bulk-restore', [HashTagController::class, 'bulkRestore'])->name('bulkRestore')->middleware('permission:hashtag.update');

        // Delete permissions
        Route::delete('/destroy/{id}', [HashTagController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+')->middleware('permission:hashtag.delete');
        Route::delete('/force-delete/{id}', [HashTagController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+')->middleware('permission:hashtag.delete');
        Route::delete('/bulk-delete', [HashTagController::class, 'bulkDelete'])->name('bulkDelete')->middleware('permission:hashtag.delete');
        Route::delete('/bulk-force-delete', [HashTagController::class, 'bulkForceDelete'])->name('bulkForceDelete')->middleware('permission:hashtag.delete');
    });

    Route::prefix('users')->name('users.')->group(function () {
        // Quản lý người dùng - Read permissions
        Route::get('/', [UserController::class, 'list'])->name('list')->middleware('permission:user.read');
        Route::get('/ajax-get-data', [UserController::class, 'ajaxGetData'])->name('ajaxGetData')->middleware('permission:user.read');
        Route::get('/ajax-get-trashed-data', [UserController::class, 'ajaxGetTrashedData'])->name('ajaxGetTrashedData')->middleware('permission:user.read');
        Route::get('/edit/{id}', [UserController::class, 'edit'])->name('edit')->where('id', '[0-9]+')->middleware('permission:user.read');

        // Create permissions
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('permission:user.create');
        Route::post('/store', [UserController::class, 'store'])->name('store')->middleware('permission:user.create');

        // Update permissions
        Route::put('/update/{id}', [UserController::class, 'update'])->name('update')->where('id', '[0-9]+')->middleware('permission:user.update');
        Route::post('/restore/{id}', [UserController::class, 'restore'])->name('restore')->where('id', '[0-9]+')->middleware('permission:user.update');
        Route::post('/bulk-restore', [UserController::class, 'bulkRestore'])->name('bulkRestore')->middleware('permission:user.update');

        // Delete permissions
        Route::delete('/destroy/{id}', [UserController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+')->middleware('permission:user.delete');
        Route::delete('/force-delete/{id}', [UserController::class, 'forceDelete'])->name('forceDelete')->where('id', '[0-9]+')->middleware('permission:user.delete');
        Route::post('/bulk-delete', [UserController::class, 'bulkDelete'])->name('bulkDelete')->middleware('permission:user.delete');
        Route::post('/bulk-force-delete', [UserController::class, 'bulkForceDelete'])->name('bulkForceDelete')->middleware('permission:user.delete');

        // Trang cá nhân & đổi mật khẩu (không cần permission, user có thể tự cập nhật profile của mình)
        Route::get('/information', [ProfileController::class, 'information'])->name('information');
        Route::put('/information', [ProfileController::class, 'updateInformation'])->name('updateInformation');
        Route::get('/change-password', [ProfileController::class, 'showChangePassword'])->name('changePassword');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('changePassword.post');
    });
    Route::prefix('roles')->name('roles.')->group(function () {
        // Roles management - sử dụng role permissions riêng
        // Read permissions
        Route::get('/', [RoleController::class, 'list'])->name('list')->middleware('permission:role.read');
        Route::get('/ajax-get-data', [RoleController::class, 'ajaxGetData'])->name('ajaxGetData')->middleware('permission:role.read');
        Route::get('/edit/{id}', [RoleController::class, 'edit'])->name('edit')->where('id', '[0-9]+')->middleware('permission:role.read');

        // Create permissions
        Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('permission:role.create');
        Route::post('/store', [RoleController::class, 'store'])->name('store')->middleware('permission:role.create');

        // Update permissions
        Route::put('/update/{id}', [RoleController::class, 'update'])->name('update')->where('id', '[0-9]+')->middleware('permission:role.update');

        // Delete permissions
        Route::delete('/destroy/{id}', [RoleController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+')->middleware('permission:role.delete');
    });
    Route::get('media', function () {
        return view('admin.modules.media.index');
    })->name('media');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::prefix('years')->name('years.')->group(function () {
            // Read permissions
            Route::get('/', [FinanceYearController::class, 'list'])->name('list')->middleware('permission:finance.read');
            Route::get('/get-by-year', [FinanceYearController::class, 'getYearByNumber'])->name('getByYear')->middleware('permission:finance.read');
            Route::get('/{id}', [FinanceYearController::class, 'show'])->name('show')->where('id', '[0-9]+')->middleware('permission:finance.read');
            Route::get('/{yearId}/months/{month}', [FinanceMonthController::class, 'show'])
                ->name('months.show')
                ->where(['yearId' => '[0-9]+', 'month' => '[1-9]|1[0-2]'])
                ->middleware('permission:finance.read');

            // Create permissions
            Route::post('/', [FinanceYearController::class, 'store'])->name('store')->middleware('permission:finance.create');

            // Update permissions
            Route::put('/{id}/target', [FinanceYearController::class, 'updateTarget'])->name('updateTarget')->where('id', '[0-9]+')->middleware('permission:finance.update');
            Route::put('/{id}/note', [FinanceYearController::class, 'updateNote'])->name('updateNote')->where('id', '[0-9]+')->middleware('permission:finance.update');
        });
        
        Route::prefix('months')->name('months.')->group(function () {
            // Read permissions
            Route::get('/{monthId}/day-details', [FinanceMonthController::class, 'dayDetails'])->name('dayDetails')->where('monthId', '[0-9]+')->middleware('permission:finance.read');
            Route::prefix('{monthId}/expenses')->name('expenses.')->group(function () {
                Route::get('/', [FinanceMonthController::class, 'getExpenses'])->name('index')->where('monthId', '[0-9]+')->middleware('permission:finance.read');
                
                // Create permissions
                Route::post('/', [FinanceMonthController::class, 'storeExpense'])->name('store')->where('monthId', '[0-9]+')->middleware('permission:finance.create');
                
                // Update permissions
                Route::put('/{id}', [FinanceMonthController::class, 'updateExpense'])->name('update')->where(['monthId' => '[0-9]+', 'id' => '[0-9]+'])->middleware('permission:finance.update');
                
                // Delete permissions
                Route::delete('/{id}', [FinanceMonthController::class, 'destroyExpense'])->name('destroy')->where(['monthId' => '[0-9]+', 'id' => '[0-9]+'])->middleware('permission:finance.delete');
            });

            // Update permissions
            Route::put('/{monthId}', [FinanceMonthController::class, 'update'])->name('update')->where('monthId', '[0-9]+')->middleware('permission:finance.update');
            Route::post('/{monthId}/lock', [FinanceMonthController::class, 'lock'])->name('lock')->where('monthId', '[0-9]+')->middleware('permission:finance.update');
        });

    });

    Route::prefix('settings')->name('settings.')->group(function () {
        // Settings - Read permissions
        Route::get('/', [SettingController::class, 'index'])->name('index')->middleware('permission:setting.read');
        Route::get('/test-email-setup', [SettingController::class, 'testEmailSetup'])->name('testEmailSetup')->middleware('permission:setting.read');
        Route::get('/test-queue', [SettingController::class, 'testQueue'])->name('testQueue')->middleware('permission:setting.read');

        // Update permissions
        Route::post('/', [SettingController::class, 'update'])->name('update')->middleware('permission:setting.update');
    });
});
// Admin Authentication routes
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

if (config('app.env') === 'local') {
Route::get('/check-ip', function (Request $request) {
    return response()->json([
        'real_ip' => $request->ip(),
        'all_ips' => $request->ips(),
        'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
        'x_forwarded_for' => $request->header('X-Forwarded-For'),
    ]);
});
}