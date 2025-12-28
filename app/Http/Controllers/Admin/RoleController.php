<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\StoreRequest;
use App\Http\Requests\Admin\Role\UpdateRequest;
use App\Repositories\RoleRepository;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function list()
    {
        return view('admin.modules.role.list');
    }

    public function ajaxGetData()
    {
        $grid = $this->roleRepository->gridData();
        $data = $this->roleRepository->filterData($grid);

        return $this->roleRepository->renderDataTables($data);
    }

    public function create()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            // Group by module (e.g., "post.create" -> "post")
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'other';
        });

        return view('admin.modules.role.create', compact('permissions'));
    }

    public function store(StoreRequest $request)
    {
        try {
            $data = $request->validated();
            $permissionIds = $data['permissions'] ?? [];
            unset($data['permissions']);

            $role = $this->roleRepository->create($data);
            
            if ($role && !empty($permissionIds)) {
                $permissions = Permission::whereIn('id', $permissionIds)->get();
                $role->syncPermissions($permissions);
            }

            return redirect()->route('admin.roles.list')->with('success', 'Thêm vai trò mới thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra')->withInput();
        }
    }

    public function edit($id)
    {
        $role = $this->roleRepository->findById($id);
        if (!$role) {
            return redirect()->route('admin.roles.list')->with('error', 'Vai trò không tồn tại');
        }

        $permissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'other';
        });

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.modules.role.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $permissionIds = $data['permissions'] ?? [];
            unset($data['permissions']);

            $role = $this->roleRepository->update($id, $data);

            if (!$role) {
                return back()->with('error', 'Vai trò không tồn tại')->withInput();
            }

            // Sync permissions
            if (!empty($permissionIds)) {
                $permissions = Permission::whereIn('id', $permissionIds)->get();
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }

            return back()->with('success', 'Cập nhật vai trò thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $role = $this->roleRepository->findById($id);
            if (!$role) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vai trò không tồn tại',
                ], 404);
            }

            // Check if role has users
            if ($role->users()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không thể xóa vai trò đang được sử dụng bởi người dùng',
                ], 422);
            }

            $this->roleRepository->delete($id);

            return response()->json([
                'status' => true,
                'message' => 'Xóa vai trò thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa vai trò',
            ], 500);
        }
    }
}

