<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private $tableName = 'users';

    private $version = 1;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (seed_version($this->tableName, $this->version)) {
            $users = [
                'avatar' => null,
                'full_name' => 'Super Admin',
                'description' => null,
                'twitter_url' => null,
                'facebook_url' => null,
                'instagram_url' => null,
                'linkedin_url' => null,
                'email' => 'kienhee.it@gmail.com',
                'phone' => '0123456789',
                'gender' => 0, // 0=male, 1=female, 2=other
                'birthday' => '2002-10-30',
                'email_verified_at' => null,
                'password' => Hash::make('123456'),
                'remember_token' => null,
                'deleted_at' => null,
                'created_at' => now()->subYears(2),
                'updated_at' => now(),
            ];
            DB::table($this->tableName)->insert($users);
        }
    }
}
