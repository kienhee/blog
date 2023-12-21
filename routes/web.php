<?php

use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\Category\CategoryController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Feedback\FeedbackController;
use App\Http\Controllers\Admin\Group\GroupController;
use App\Http\Controllers\Admin\Post\PostController;
use App\Http\Controllers\Admin\Project\ProjectController;
use App\Http\Controllers\Admin\Tag\TagController;
use App\Http\Controllers\Admin\Trend\TrendController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\ClientController;

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

Route::prefix('/')->name('client.')->group(function () {
    Route::get("/", [ClientController::class, 'home'])->name('index');
    Route::get("tac-gia", [ClientController::class, 'author'])->name('author');
    Route::get("bai-viet/{slug}", [ClientController::class, 'blog'])->name('blog');
    Route::get("du-an/{slug}", [ClientController::class, 'work'])->name('work');
});
Route::prefix('/dashboard')->name('dashboard.')->middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, "dashboard"])->name('index');
    Route::prefix('categories')->name('category.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/add', [categoryController::class, 'add'])->name('add');
        Route::post('/add', [categoryController::class, 'store'])->name('store');
        Route::get('/edit/{category}', [categoryController::class, 'edit'])->name('edit');
        Route::put('/edit/{id}', [categoryController::class, 'update'])->name('update');
        Route::delete('/soft-delete/{id}', [categoryController::class, 'softDelete'])->name('soft-delete');
        Route::delete('/force-delete/{id}', [categoryController::class, 'forceDelete'])->name('force-delete');
        Route::delete('/restore/{id}', [categoryController::class, 'restore'])->name('restore');
    });
    Route::prefix('/posts')->name('post.')->group(function () {
        Route::get("/", [PostController::class, 'index'])->name('index');
        Route::get("/add", [PostController::class, 'add'])->name('add');
        Route::post('/add', [PostController::class, 'store'])->name('store');
        Route::get('/edit/{post}', [PostController::class, 'edit'])->name('edit');
        Route::put('/edit/{id}', [PostController::class, 'update'])->name('update');
        Route::delete('/soft-delete/{id}', [PostController::class, 'softDelete'])->name('soft-delete');
        Route::delete('/force-delete/{id}', [PostController::class, 'forceDelete'])->name('force-delete');
        Route::delete('/restore/{id}', [PostController::class, 'restore'])->name('restore');
    });

    Route::prefix('/tags')->name('tag.')->group(function () {
        Route::get('/', [TagController::class, 'index'])->name('index');
        Route::get('/add', [TagController::class, 'add'])->name('add');
        Route::post('/add', [TagController::class, 'store'])->name('store');
        Route::get('/edit/{tag}', [TagController::class, 'edit'])->name('edit');
        Route::put('/edit/{id}', [TagController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [TagController::class, 'delete'])->name('delete');
    });
    Route::prefix('projects')->name('project.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/add', [ProjectController::class, 'add'])->name('add');
        Route::post('/add', [ProjectController::class, 'store'])->name('store');
        Route::get('/edit/{project}', [ProjectController::class, 'edit'])->name('edit');
        Route::put('/edit/{id}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/soft-delete/{id}', [ProjectController::class, 'softDelete'])->name('soft-delete');
        Route::delete('/force-delete/{id}', [ProjectController::class, 'forceDelete'])->name('force-delete');
        Route::delete('/restore/{id}', [ProjectController::class, 'restore'])->name('restore');
    });
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::get('/', [FeedbackController::class, 'index'])->name('index');
        Route::get('/add', [FeedbackController::class, 'add'])->name('add');
        Route::post('/add', [FeedbackController::class, 'store'])->name('store');
        Route::get('/edit/{feedback}', [FeedbackController::class, 'edit'])->name('edit');
        Route::put('/edit/{id}', [FeedbackController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [FeedbackController::class, 'delete'])->name('delete');
    });

    Route::prefix('groups')->name('group.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::get('/add', [GroupController::class, 'add'])->name('add');
        Route::post('/add', [GroupController::class, 'store'])->name('store');
        Route::get('/edit/{group}', [GroupController::class, 'edit'])->name('edit');
        Route::put('/edit/{id}', [GroupController::class, 'update'])->name('update');

        Route::delete('/delete/{id}', [GroupController::class, 'delete'])->name('delete');
    });
    Route::prefix('users')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/add', [UserController::class, 'add'])->name('add');
        Route::post('/add', [UserController::class, 'store'])->name('store');
        Route::get('/edit/{user}', [UserController::class, 'edit'])->name('edit');
        Route::put('/edit/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/soft-delete/{id}', [UserController::class, 'softDelete'])->name('soft-delete');
        Route::delete('/force-delete/{id}', [UserController::class, 'forceDelete'])->name('force-delete');
        Route::delete('/restore/{id}', [UserController::class, 'restore'])->name('restore');
        Route::get('/account-setting', [UserController::class, 'AccountSetting'])->name('account-setting');
        Route::get('/change-password', [UserController::class, 'changePw'])->name('change-password');
        Route::put('/change-password/{email}', [UserController::class, 'handleChangePassword'])->name('handle-change-password');
    });
    Route::get('/media', function () {
        return view('admin.media.index');
    })->name('media');
});
Route::prefix('/auth-dashboard')->name('auth.')->group(function () {
    Route::get('/login', [AuthController::class, 'loginView'])->name('loginView');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::delete('/logout', [AuthController::class, 'logout'])->name('logout');
});

//Routes dành cho các mẫu
require __DIR__ . '/template.php';
Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});
