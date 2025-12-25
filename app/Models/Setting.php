<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    /**
     * Lấy giá trị và unserialize; trả về $default nếu chưa có.
     */
    public static function getValue(string $key, $default = null)
    {
        $value = self::where('key', $key)->value('value');

        if (is_null($value)) {
            return $default;
        }

        try {
            return unserialize($value);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Lưu giá trị sau khi serialize.
     */
    public static function setValue(string $key, $data)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => serialize($data)]
        );
    }
}
