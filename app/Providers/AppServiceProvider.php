<?php

namespace App\Providers;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Share categories menu with nav view
        View::composer('client.layouts.sections.nav', function ($view) {
            $categoryRepository = app(CategoryRepository::class);
            $categories = $categoryRepository->getCategoryByType()->toArray();
            
            // Build menu structure for navigation using Category model's buildMenuClient
            $categoryModel = new Category();
            $categoryMenu = $categoryModel->buildMenuClient($categories, null);
            
            $view->with('categoryMenu', $categoryMenu);
        });
    }
}
