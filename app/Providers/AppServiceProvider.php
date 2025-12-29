<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Repositories\CategoryRepository;
use App\Repositories\HashTagRepository;
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

        // Share top hashtags with footer view
        View::composer('client.layouts.sections.footer', function ($view) {
            $hashTagRepository = app(HashTagRepository::class);
            $allHashtags = $hashTagRepository->getTopHashtagsByPostCount(10);
            
            // Share settings for footer
            $footerSettings = [
                'address' => Setting::getValue('address', ''),
                'phone' => Setting::getValue('phone', ''),
                'email' => Setting::getValue('email', ''),
                'facebook' => Setting::getValue('facebook', ''),
                'site_name' => Setting::getValue('site_name', env('APP_NAME', 'Blog')),
                'map' => Setting::getValue('map', ''),
                // Social media links
                'social' => [
                    'facebook' => Setting::getValue('facebook', ''),
                    'youtube' => Setting::getValue('youtube', ''),
                    'twitter' => Setting::getValue('twitter', ''),
                    'instagram' => Setting::getValue('instagram', ''),
                    'tiktok' => Setting::getValue('tiktok', ''),
                    'linkedin' => Setting::getValue('linkedin', ''),
                    'telegram' => Setting::getValue('telegram', ''),
                    'pinterest' => Setting::getValue('pinterest', ''),
                ],
            ];
            
            $view->with('allHashtags', $allHashtags);
            $view->with('footerSettings', $footerSettings);
        });
    }
}
