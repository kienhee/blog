<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TemplateController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
| -> Các routes dành cho các mẫu
*/

Route::prefix('/dashboard')->name('dashboard.')->middleware('auth')->group(function () {
    Route::prefix('/templates')->name('templates.')->group(function () {
        Route::prefix('/account-settings')->name('account-settings.')->group(function () {
            Route::get('/account', [TemplateController::class, 'Account'])->name('account');
            Route::get('/notifications', [TemplateController::class, 'Notifications'])->name('notifications');
            Route::get('/connections', [TemplateController::class, 'Connections'])->name('connections');
        });
        Route::prefix('/auth')->name('auth.')->group(function () {
            Route::get('/login', [TemplateController::class, 'Login'])->name('login');
            Route::get('/register', [TemplateController::class, 'Register'])->name('register');
            Route::get('/forgot-password', [TemplateController::class, 'ForgotPassword'])->name('forgot-password');
        });
        Route::prefix('/misc')->name('misc.')->group(function () {
            Route::get('/error', [TemplateController::class, 'Error'])->name('error');
            Route::get('/under-maintenance', [TemplateController::class, 'UnderMaintenance'])->name('under-maintenance');
        });
        Route::prefix('/components')->name('components.')->group(function () {
            Route::get('/boxicons', [TemplateController::class, 'Boxicons'])->name('boxicons');
            Route::get('/cards', [TemplateController::class, 'Cards'])->name('cards');
            Route::get('/accordion', [TemplateController::class, 'Accordion'])->name('accordion');
            Route::get('/alerts', [TemplateController::class, 'Alerts'])->name('alerts');
            Route::get('/badges', [TemplateController::class, 'Badges'])->name('badges');
            Route::get('/buttons', [TemplateController::class, 'Buttons'])->name('buttons');
            Route::get('/carousel', [TemplateController::class, 'Carousel'])->name('carousel');
            Route::get('/collapse', [TemplateController::class, 'Collapse'])->name('collapse');
            Route::get('/dropdowns', [TemplateController::class, 'Dropdowns'])->name('dropdowns');
            Route::get('/footer', [TemplateController::class, 'Footer'])->name('footer');
            Route::get('/list-groups', [TemplateController::class, 'ListGroups'])->name('list-groups');
            Route::get('/modals', [TemplateController::class, 'Modals'])->name('modals');
            Route::get('/navbar', [TemplateController::class, 'Navbar'])->name('navbar');
            Route::get('/offcanvas', [TemplateController::class, 'Offcanvas'])->name('offcanvas');
            Route::get('/pagination-breadcrumbs', [TemplateController::class, 'PaginationBreadcrumbs'])->name('pagination-breadcrumbs');
            Route::get('/progress', [TemplateController::class, 'Progress'])->name('progress');
            Route::get('/spinners', [TemplateController::class, 'Spinners'])->name('spinners');
            Route::get('/tabs-pills', [TemplateController::class, 'TabsPills'])->name('tabs-pills');
            Route::get('/toasts', [TemplateController::class, 'Toasts'])->name('toasts');
            Route::get('/tooltips-popovers', [TemplateController::class, 'TooltipsPopovers'])->name('tooltips-popovers');
            Route::get('/typography', [TemplateController::class, 'Typography'])->name('typography');
        });
        Route::prefix('/extended-ui')->name('extended-ui.')->group(function () {
            Route::get('/perfect-scrollbar', [TemplateController::class, 'PerfectScrollbar'])->name('perfect-scrollbar');
            Route::get('/text-divider', [TemplateController::class, 'TextDivider'])->name('text-divider');
        });
        Route::prefix('/form-elements')->name('form-elements.')->group(function () {
            Route::get('/basic-inputs', [TemplateController::class, 'BasicInputs'])->name('basic-inputs');
            Route::get('/input-groups', [TemplateController::class, 'InputGroups'])->name('input-groups');
        });
        Route::prefix('/form-layouts')->name('form-layouts.')->group(function () {
            Route::get('/horizontal', [TemplateController::class, 'HorizontalForm'])->name('horizontal');
            Route::get('/vertical', [TemplateController::class, 'VerticalForm'])->name('vertical');
        });
        Route::prefix('/tables')->name('tables.')->group(function () {
            Route::get('/', [TemplateController::class, 'Tables'])->name('index');
        });
    });
});