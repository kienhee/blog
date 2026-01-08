<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Client Cache Helper
 * 
 * Quản lý cache keys và invalidation cho client side
 */
class ClientCacheHelper
{
    /**
     * Cache prefix cho client
     */
    const PREFIX = 'client:';

    /**
     * Cache TTL (Time To Live) - 1 giờ
     */
    const TTL = 3600;

    /**
     * Cache TTL cho dữ liệu ít thay đổi - 24 giờ
     */
    const TTL_LONG = 86400;

    /**
     * Cache keys
     */
    const KEY_HOME_LATEST_POSTS = 'home:latest-posts';
    const KEY_HOME_FEATURED_POST = 'home:featured-post';
    const KEY_HOME_RECENT_POSTS = 'home:recent-posts';
    const KEY_HOME_SIDEBAR_POSTS = 'home:sidebar-posts';
    const KEY_HOME_CATEGORIES = 'home:categories';
    
    const KEY_POST_DETAIL = 'post:detail:';
    const KEY_POST_RELATED = 'post:related:';
    const KEY_POST_PREV = 'post:prev:';
    const KEY_POST_NEXT = 'post:next:';
    const KEY_POST_CATEGORIES = 'post:categories';
    const KEY_POST_HASHTAGS = 'post:hashtags';
    
    const KEY_CATEGORY_POSTS = 'category:posts:';
    const KEY_CATEGORY_DETAIL = 'category:detail:';
    
    const KEY_HASHTAG_POSTS = 'hashtag:posts:';
    const KEY_HASHTAG_DETAIL = 'hashtag:detail:';
    
    const KEY_POSTS_LIST = 'posts:list:';

    /**
     * Get full cache key
     */
    public static function key(string $key): string
    {
        return self::PREFIX . $key;
    }

    /**
     * Get cache với key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::get(self::key($key), $default);
    }

    /**
     * Set cache với key
     */
    public static function put(string $key, $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? self::TTL;
        return Cache::put(self::key($key), $value, $ttl);
    }

    /**
     * Remember cache (get hoặc set)
     */
    public static function remember(string $key, callable $callback, int $ttl = null)
    {
        $ttl = $ttl ?? self::TTL;
        return Cache::remember(self::key($key), $ttl, $callback);
    }

    /**
     * Forget cache
     */
    public static function forget(string $key): bool
    {
        return Cache::forget(self::key($key));
    }

    /**
     * Clear all client cache
     */
    public static function clearAll(): bool
    {
        // Clear all cache with prefix
        // Note: This requires cache driver that supports tags (Redis, Memcached)
        // For database/file cache, we need to clear manually
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggedCache) {
            return Cache::tags([self::PREFIX])->flush();
        }
        
        // Fallback: Clear specific keys
        return self::clearHomeCache() && 
               self::clearPostCache() && 
               self::clearCategoryCache() && 
               self::clearHashtagCache();
    }

    /**
     * Clear home page cache
     */
    public static function clearHomeCache(): bool
    {
        $keys = [
            self::KEY_HOME_LATEST_POSTS,
            self::KEY_HOME_FEATURED_POST,
            self::KEY_HOME_RECENT_POSTS,
            self::KEY_HOME_SIDEBAR_POSTS,
            self::KEY_HOME_CATEGORIES,
        ];

        $result = true;
        foreach ($keys as $key) {
            $result = $result && self::forget($key);
        }

        return $result;
    }

    /**
     * Clear post cache
     */
    public static function clearPostCache(?int $postId = null): bool
    {
        // Clear all post cache
        if (!$postId) {
            // Clear home cache (contains posts)
            self::clearHomeCache();
            
            // Clear post list cache
            // Note: We can't clear all paginated cache keys easily
            // So we rely on TTL expiration
            
            return true;
        }

        // Clear specific post cache
        $keys = [
            self::KEY_POST_DETAIL . $postId,
            self::KEY_POST_RELATED . $postId,
            self::KEY_POST_PREV . $postId,
            self::KEY_POST_NEXT . $postId,
        ];

        $result = true;
        foreach ($keys as $key) {
            $result = $result && self::forget($key);
        }

        // Also clear home cache (may contain this post)
        self::clearHomeCache();

        return $result;
    }

    /**
     * Clear category cache
     */
    public static function clearCategoryCache(?int $categoryId = null): bool
    {
        if (!$categoryId) {
            // Clear all category cache
            self::clearHomeCache(); // Categories are in home cache
            return true;
        }

        // Clear specific category cache
        $keys = [
            self::KEY_CATEGORY_POSTS . $categoryId,
            self::KEY_CATEGORY_DETAIL . $categoryId,
        ];

        $result = true;
        foreach ($keys as $key) {
            $result = $result && self::forget($key);
        }

        // Also clear home cache (may contain this category)
        self::clearHomeCache();

        return $result;
    }

    /**
     * Clear hashtag cache
     */
    public static function clearHashtagCache(?int $hashtagId = null): bool
    {
        if (!$hashtagId) {
            return true;
        }

        // Clear specific hashtag cache
        $keys = [
            self::KEY_HASHTAG_POSTS . $hashtagId,
            self::KEY_HASHTAG_DETAIL . $hashtagId,
        ];

        $result = true;
        foreach ($keys as $key) {
            $result = $result && self::forget($key);
        }

        return $result;
    }

    /**
     * Clear posts list cache (for pagination)
     */
    public static function clearPostsListCache(): bool
    {
        // Note: Pagination cache keys are dynamic, so we rely on TTL
        // But we can clear home cache which contains posts
        return self::clearHomeCache();
    }

    /**
     * Generate cache key for paginated posts
     */
    public static function getPostsListKey(string $page = '1', ?string $search = null): string
    {
        $key = self::KEY_POSTS_LIST . 'page:' . $page;
        if ($search) {
            $key .= ':search:' . md5($search);
        }
        return $key;
    }

    /**
     * Generate cache key for category posts
     */
    public static function getCategoryPostsKey(int $categoryId, string $page = '1'): string
    {
        return self::KEY_CATEGORY_POSTS . $categoryId . ':page:' . $page;
    }

    /**
     * Generate cache key for hashtag posts
     */
    public static function getHashtagPostsKey(int $hashtagId, string $page = '1'): string
    {
        return self::KEY_HASHTAG_POSTS . $hashtagId . ':page:' . $page;
    }
}

