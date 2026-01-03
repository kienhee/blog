<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'password',
        'note',
        'order',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Relationship với User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor để decrypt password khi lấy ra
     */
    public function getPasswordAttribute($value)
    {
        if (!empty($value)) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($value);
            } catch (\Exception $e) {
                // Nếu không decrypt được (có thể là giá trị cũ), trả về như cũ
                return $value;
            }
        }
        return $value;
    }

    /**
     * Mutator để mã hóa password trước khi lưu
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = \Illuminate\Support\Facades\Crypt::encryptString($value);
        }
    }
}
