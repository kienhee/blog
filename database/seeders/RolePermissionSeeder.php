<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Tạo permissions cho các modules
        //
        // CÁCH THÊM QUYỀN MỚI:
        // 1. Thêm vào mảng $permissionDefinitions với id mới (không trùng với id hiện có)
        // 2. Format: ['id' => {id}, 'name' => '{module}.{action}', 'title' => 'Tiêu đề tiếng Việt']
        // 3. Ví dụ: ['id' => 29, 'name' => 'comment.create', 'title' => 'Thêm bình luận']
        // 4. Chạy: php artisan db:seed --class=RolePermissionSeeder
        //
        // CÁCH CẬP NHẬT QUYỀN:
        // - Chỉ cần sửa 'title' hoặc 'name' trong mảng (giữ nguyên 'id')
        // - Chạy seeder lại: php artisan db:seed --class=RolePermissionSeeder
        //
        $permissionDefinitions = [
            // Post module
            ['id' => 1, 'name' => 'post.read', 'title' => 'Xem bài viết'],
            ['id' => 2, 'name' => 'post.update', 'title' => 'Sửa bài viết'],
            ['id' => 3, 'name' => 'post.delete', 'title' => 'Xóa bài viết'],
            ['id' => 4, 'name' => 'post.create', 'title' => 'Thêm bài viết'],

            // Category module
            ['id' => 5, 'name' => 'category.read', 'title' => 'Xem danh mục'],
            ['id' => 6, 'name' => 'category.update', 'title' => 'Sửa danh mục'],
            ['id' => 7, 'name' => 'category.delete', 'title' => 'Xóa danh mục'],
            ['id' => 8, 'name' => 'category.create', 'title' => 'Thêm danh mục'],

            // Hashtag module
            ['id' => 9, 'name' => 'hashtag.read', 'title' => 'Xem hashtag'],
            ['id' => 10, 'name' => 'hashtag.update', 'title' => 'Sửa hashtag'],
            ['id' => 11, 'name' => 'hashtag.delete', 'title' => 'Xóa hashtag'],
            ['id' => 12, 'name' => 'hashtag.create', 'title' => 'Thêm hashtag'],

            // User module
            ['id' => 13, 'name' => 'user.read', 'title' => 'Xem người dùng'],
            ['id' => 14, 'name' => 'user.update', 'title' => 'Sửa người dùng'],
            ['id' => 15, 'name' => 'user.delete', 'title' => 'Xóa người dùng'],
            ['id' => 16, 'name' => 'user.create', 'title' => 'Thêm người dùng'],

            // Role module
            ['id' => 17, 'name' => 'role.read', 'title' => 'Xem vai trò'],
            ['id' => 18, 'name' => 'role.update', 'title' => 'Sửa vai trò'],
            ['id' => 19, 'name' => 'role.delete', 'title' => 'Xóa vai trò'],
            ['id' => 20, 'name' => 'role.create', 'title' => 'Thêm vai trò'],

            // Contact module
            ['id' => 21, 'name' => 'contact.read', 'title' => 'Xem liên hệ'],
            ['id' => 22, 'name' => 'contact.update', 'title' => 'Sửa liên hệ'],
            ['id' => 23, 'name' => 'contact.delete', 'title' => 'Xóa liên hệ'],
            ['id' => 24, 'name' => 'contact.create', 'title' => 'Thêm liên hệ'],

            // Setting module (chỉ có read và update)
            ['id' => 25, 'name' => 'setting.read', 'title' => 'Xem cài đặt'],
            ['id' => 26, 'name' => 'setting.update', 'title' => 'Sửa cài đặt'],

            // Newsletter module
            ['id' => 29, 'name' => 'newsletter.read', 'title' => 'Xem newsletter'],
            ['id' => 30, 'name' => 'newsletter.update', 'title' => 'Sửa newsletter'],
            ['id' => 31, 'name' => 'newsletter.delete', 'title' => 'Xóa newsletter'],
            ['id' => 32, 'name' => 'newsletter.create', 'title' => 'Thêm newsletter'],

            // Comment module
            ['id' => 33, 'name' => 'comment.read', 'title' => 'Xem bình luận'],
            ['id' => 34, 'name' => 'comment.update', 'title' => 'Sửa bình luận'],
            ['id' => 35, 'name' => 'comment.delete', 'title' => 'Xóa bình luận'],
            ['id' => 36, 'name' => 'comment.create', 'title' => 'Thêm bình luận'],

            // Finance module
            ['id' => 37, 'name' => 'finance.read', 'title' => 'Xem tài chính'],
            ['id' => 38, 'name' => 'finance.update', 'title' => 'Sửa tài chính'],
            ['id' => 39, 'name' => 'finance.delete', 'title' => 'Xóa tài chính'],
            ['id' => 40, 'name' => 'finance.create', 'title' => 'Thêm tài chính'],
        ];

        $permissions = [];
        foreach ($permissionDefinitions as $def) {
            // Kiểm tra xem có permission với name này nhưng id khác không (cần xóa trước)
            $existingPermissionByName = Permission::where('name', $def['name'])
                ->where('guard_name', 'web')
                ->where('id', '!=', $def['id'])
                ->first();

            if ($existingPermissionByName) {
                // Đã tồn tại permission với name này nhưng id khác, cần migrate dữ liệu
                $oldId = $existingPermissionByName->id;

                // Lấy danh sách các role_id và model_id từ bảng pivot cũ
                $rolePermissions = DB::table('role_has_permissions')
                    ->where('permission_id', $oldId)
                    ->get(['role_id']);
                $modelPermissions = DB::table('model_has_permissions')
                    ->where('permission_id', $oldId)
                    ->get(['model_type', 'model_id']);

                // Xóa các record cũ khỏi bảng pivot
                DB::table('role_has_permissions')->where('permission_id', $oldId)->delete();
                DB::table('model_has_permissions')->where('permission_id', $oldId)->delete();

                // Xóa permission cũ
                $existingPermissionByName->delete();

                // Insert lại vào bảng pivot với id mới (nếu permission với id mới đã tồn tại)
                $newPermission = Permission::find($def['id']);
                if ($newPermission) {
                    foreach ($rolePermissions as $rp) {
                        // Chỉ insert nếu chưa tồn tại
                        DB::table('role_has_permissions')->updateOrInsert(
                            ['role_id' => $rp->role_id, 'permission_id' => $def['id']]
                        );
                    }
                    foreach ($modelPermissions as $mp) {
                        // Chỉ insert nếu chưa tồn tại
                        DB::table('model_has_permissions')->updateOrInsert(
                            [
                                'model_type' => $mp->model_type,
                                'model_id' => $mp->model_id,
                                'permission_id' => $def['id'],
                            ]
                        );
                    }
                }
            }

            // Kiểm tra xem permission với id cố định đã tồn tại chưa
            $permission = Permission::find($def['id']);

            if ($permission) {
                // Permission với id này đã tồn tại, cập nhật name và title nếu cần
                $needUpdate = false;
                if ($permission->name !== $def['name'] || $permission->guard_name !== 'web') {
                    $permission->name = $def['name'];
                    $permission->guard_name = 'web';
                    $needUpdate = true;
                }
                if (! $permission->title || $permission->title !== $def['title']) {
                    $permission->title = $def['title'];
                    $needUpdate = true;
                }
                if ($needUpdate) {
                    $permission->save();
                }
            } else {
                // Tạo mới với id cố định
                DB::table('permissions')->insert([
                    'id' => $def['id'],
                    'name' => $def['name'],
                    'guard_name' => 'web',
                    'title' => $def['title'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $permission = Permission::find($def['id']);
            }

            $permissions[] = $permission;
        }

        // Tạo roles
        $guestRole = Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']);
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        // Gán tất cả permissions cho superadmin
        $superAdminRole->syncPermissions($permissions);
    }
}
