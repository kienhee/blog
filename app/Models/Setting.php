<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /**
     * Local in-memory cache cho settings trong 1 request.
     *
     * @var array<string, mixed>
     */
    protected static array $settingsCache = [];

    /**
     * Cache key dùng cho Laravel cache.
     */
    protected const CACHE_KEY_ALL = 'settings:all';

    /**
     * TTL cho Laravel cache (giây).
     * 1 giờ là đủ vì settings ít thay đổi.
     */
    protected const CACHE_TTL = 3600;

    /**
     * Load tất cả settings từ cache (hoặc database nếu chưa có),
     * unserialize sẵn và lưu vào static cache để dùng lại trong request hiện tại.
     *
     * @return array<string, mixed>
     */
    protected static function loadAllSettings(): array
    {
        // Đã có trong static cache cho request hiện tại
        if (! empty(self::$settingsCache)) {
            return self::$settingsCache;
        }

        // Lấy từ Laravel cache (giữa các request)
        $rawSettings = Cache::remember(
            self::CACHE_KEY_ALL,
            self::CACHE_TTL,
            static fn () => self::query()->pluck('value', 'key')->toArray()
        );

        $settings = [];

        foreach ($rawSettings as $key => $value) {
            if ($value === null) {
                $settings[$key] = null;
                continue;
            }

            try {
                $settings[$key] = unserialize($value);
            } catch (\Throwable $e) {
                // Nếu dữ liệu cũ không phải serialize hợp lệ thì bỏ qua
                $settings[$key] = null;
            }
        }

        self::$settingsCache = $settings;

        return self::$settingsCache;
    }

    /**
     * Lấy giá trị setting; trả về $default nếu chưa có hoặc lỗi dữ liệu.
     */
    public static function getValue(string $key, $default = null)
    {
        $settings = self::loadAllSettings();

        if (array_key_exists($key, $settings) && $settings[$key] !== null) {
            return $settings[$key];
        }

        return $default;
    }

    /**
     * Lưu giá trị sau khi serialize và làm mới cache.
     */
    public static function setValue(string $key, $data)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => serialize($data)]
        );

        // Làm mới cả Laravel cache và static cache để lần gọi tiếp theo không dùng dữ liệu cũ
        Cache::forget(self::CACHE_KEY_ALL);
        self::$settingsCache = [];

        return $setting;
    }
}
