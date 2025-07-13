<?php

use App\Http\Controllers\ClientController;
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
    Route::get("/", [DashboardController::class, "index"])->name('analysis');
    Route::get("/contacts", [ContactController::class, "index"])->name('contacts');
    Route::get("/ajax-get-data-contacts", [ContactController::class, "ajaxGetDataContact"])->name('ajaxGetDataContact');
});

// auth routes
Route::prefix("/auth")->group(function () {
    Route::get("/login", [AuthController::class, "loginView"])->name("auth.login");
    Route::post("/login", [AuthController::class, "login"])->name("auth.login.post");
    Route::post("/logout", [AuthController::class, "logout"])->name("auth.logout");
});
