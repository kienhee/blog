<?php

use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
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

Route::prefix("/")->middleware('localization')->group(function () {
    Route::get("/", [ClientController::class, "index"])->name("home");
    Route::get("/about", [ClientController::class, "about"])->name("about");
    Route::get("/contact", [ClientController::class, "contact"])->name("contact");
    Route::post("/contact", [\App\Http\Controllers\ContactController::class, "store"])->name("contact.store");
    Route::get("/change-language", [ClientController::class, "changeLanguage"])->name("changeLanguage");
});


Route::prefix("/dashboard")->middleware('auth')->group(function () {
    Route::get("/", function (){
        return view("pages.admin.dashboard.index");
    })->name('dashboard');
});

Route::prefix("/auth")->group(function () {
    Route::get("/login", [AuthController::class, "loginView"])->name("auth.login");
    Route::post("/login", [AuthController::class, "login"])->name("auth.login.post");
    Route::post("/logout", [AuthController::class, "logout"])->name("auth.logout");
});
