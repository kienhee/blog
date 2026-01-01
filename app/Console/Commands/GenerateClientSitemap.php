<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateClientSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sitemap.xml for client-facing pages';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating client sitemap...');
        $startTime = microtime(true);

        try {
            $sitemap = Sitemap::create();
            $urlCount = 0;

            // Static client pages with priority and changefreq
            $staticPages = [
                ['route' => 'client.home', 'priority' => 1.0, 'changefreq' => Url::CHANGE_FREQUENCY_DAILY],
                ['route' => 'client.posts', 'priority' => 0.9, 'changefreq' => Url::CHANGE_FREQUENCY_DAILY],
                ['route' => 'client.contact', 'priority' => 0.5, 'changefreq' => Url::CHANGE_FREQUENCY_MONTHLY],
                ['route' => 'client.about', 'priority' => 0.5, 'changefreq' => Url::CHANGE_FREQUENCY_MONTHLY],
            ];

            foreach ($staticPages as $page) {
                // Use absolute URL for sitemap
                $url = route($page['route'], [], true);
                $sitemap->add(
                    Url::create($url)
                        ->setPriority($page['priority'])
                        ->setChangeFrequency($page['changefreq'])
                );
                $urlCount++;
            }

            // Categories - optimized query with orderBy for consistency
            $categoryQuery = Category::query()
                ->whereNull('deleted_at')
                ->select(['id', 'slug', 'updated_at'])
                ->orderBy('id');

            $categoryCount = 0;
            $categoryQuery->chunk(200, function ($categories) use ($sitemap, &$categoryCount) {
                foreach ($categories as $category) {
                    // Use absolute URL for sitemap
                    $urlString = route('client.category', ['slug' => $category->slug], true);
                    $url = Url::create($urlString)
                        ->setPriority(0.8)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY);

                    if ($category->updated_at) {
                        $url->setLastModificationDate($category->updated_at);
                    }

                    $sitemap->add($url);
                    $categoryCount++;
                }
            });

            if ($categoryCount > 0) {
                $this->info("✓ Added {$categoryCount} categories");
            }

            // Published posts - optimized query with orderBy and single query
            $postQuery = Post::query()
                ->whereNull('deleted_at')
                ->where('status', Post::STATUS_PUBLISHED)
                ->where(function ($query) {
                    $query->whereNull('scheduled_at')
                        ->orWhere('scheduled_at', '<=', now());
                })
                ->select(['id', 'slug', 'updated_at'])
                ->orderBy('updated_at', 'desc'); // Most recent first

            $postCount = 0;
            $postQuery->chunk(200, function ($posts) use ($sitemap, &$postCount) {
                foreach ($posts as $post) {
                    // Use absolute URL for sitemap
                    $urlString = route('client.post', ['slug' => $post->slug], true);
                    $url = Url::create($urlString)
                        ->setPriority(0.7)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY);

                    if ($post->updated_at) {
                        $url->setLastModificationDate($post->updated_at);
                    }

                    $sitemap->add($url);
                    $postCount++;
                }
            });

            if ($postCount > 0) {
                $this->info("✓ Added {$postCount} posts");
            }

            // Write sitemap to file
            $sitemapPath = public_path('sitemap.xml');
            
            // Ensure directory exists
            $sitemapDir = dirname($sitemapPath);
            if (!is_dir($sitemapDir)) {
                mkdir($sitemapDir, 0755, true);
                $this->info("✓ Created directory: {$sitemapDir}");
            }
            
            // Check if sitemap.xml exists, if not create new file
            if (!file_exists($sitemapPath)) {
                $this->info("✓ Sitemap.xml not found, creating new file...");
            }
            
            $sitemap->writeToFile($sitemapPath);

            $totalUrls = $urlCount + $categoryCount + $postCount;
            $executionTime = round(microtime(true) - $startTime, 2);

            $this->info("✓ Sitemap generated successfully");
            $this->info("  Location: {$sitemapPath}");
            $this->info("  Total URLs: {$totalUrls}");
            $this->info("  Execution time: {$executionTime}s");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to generate sitemap: {$e->getMessage()}");
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            } else {
                $this->error("Run with --verbose flag for full stack trace");
            }

            Log::error('Sitemap generation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}


