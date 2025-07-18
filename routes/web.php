<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
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
// client routes
Route::prefix("/")->middleware('localization')->group(function () {
    Route::get("/", [ClientController::class, "index"])->name("home");
    Route::get("/about", [ClientController::class, "about"])->name("about");
    Route::get("/contact", [ClientController::class, "contact"])->name("contact");
    Route::post("/contact", [ContactController::class, "store"])->name("contact.store");
    Route::get("/change-language", [ClientController::class, "changeLanguage"])->name("changeLanguage");
});

// dashboard routes
Route::prefix("/dashboard")->name('dashboard.')->middleware('auth')->group(function () {
    // DashboardController
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    //    PostController routes
    Route::prefix("/posts")->name('posts.')->group(function () {
        Route::get("/create", [PostController::class, "create"])->name("create");
    });
    // ContactController routes
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get("/", [ContactController::class, "index"])->name('index');
        Route::get("/ajax-get-data-contacts", [ContactController::class, "ajaxGetDataContact"])->name('ajaxGetDataContact');
        Route::post('/confirm/{id?}', [ContactController::class, 'confirm'])->name('confirm');
    });
});

// auth routes
Route::prefix("/auth")->group(function () {
    Route::get("/login", [AuthController::class, "loginView"])->name("auth.login");
    Route::post("/login", [AuthController::class, "login"])->name("auth.login.post");
    Route::post("/logout", [AuthController::class, "logout"])->name("auth.logout");
});

Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});
