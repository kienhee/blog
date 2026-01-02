<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class System
{
    public static $version_view = 6; // Tăng view mỗi khi thay đổi css or js
    /**
     * Get the admin asset URL with versioning.
     *
     * @param string $path  url assets
     * @return string
     */
    public static function asset_admin_url($path)
    {
        return asset("resources/admin/$path?ver=" . self::$version_view);
    }

    /**
     * Get the client asset URL with versioning.
     *
     * @param string $path  url assets
     * @return string
     */
    public static function asset_client_url($path)
    {
        return asset("resources/client/$path?ver=" . self::$version_view);
    }

    /**
     * Get public asset URL with versioning (for files in public directory)
     *
     * @param string $path  file path relative to public directory (e.g., 'css/post-content.css')
     * @return string
     */
    public static function asset_shared_url($path)
    {
        $filePath = public_path($path);
        $version = file_exists($filePath) ? filemtime($filePath) : self::$version_view;

        return asset("resources/shared/$path?v=".$version);
    }

    /**
     * Summary of handle SeedVersion
     * @param mixed $tableName
     * @param mixed $version
     * @param bool $isTruncate
     * @return bool
     */
    public static function SeedVersion($tableName, $version = 1, $isTruncate = true)
    {
        if (empty($tableName) || empty($version)) {
            Log::error('Missing table name or version');
            return false;
        }

        $migrationName = "seed-{$tableName}";
        $data = DB::table('seeder_vers')->where('name', $migrationName)->first();

        if (!$data) {
            DB::table('seeder_vers')->insert([
                'name' => $migrationName,
                'version' => $version,
            ]);
            return true;
        }

        if ($version <= $data->version) {
            Log::error('Input version is not greater than current DB version');
            return false;
        }

        if ($isTruncate) {
            DB::table($tableName)->truncate();
        }

        DB::table('seeder_vers')->where('name', $migrationName)->update([
            'version' => $version,
        ]);
        return true;
    }
}
