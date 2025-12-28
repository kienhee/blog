<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreRequest;
use App\Http\Requests\Admin\User\UpdateRequest;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userRepository;
    protected $roleRepository;

    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    public function list()
    {
        return view('admin.modules.user.list');
    }

    public function ajaxGetData()
    {
        $grid = $this->userRepository->gridData();
        $data = $this->userRepository->filterData($grid);

        return $this->userRepository->renderDataTables($data);
    }

    public function ajaxGetTrashedData()
    {
        $grid = $this->userRepository->gridTrashedData();
        $data = $this->userRepository->filterData($grid);

        return $this->userRepository->renderTrashedDataTables($data);
    }

    public function create()
    {
        $roles = $this->roleRepository->getAllForSelect();
        return view('admin.modules.user.create', compact('roles'));
    }

    public function store(StoreRequest $request)
    {
        try {
            $data = $request->validated();

            // Chuẩn hóa giới tính về dạng số theo schema DB: 0=male,1=female,2=other
            $genderMap = [
                'male' => 0,
                'female' => 1,
                'other' => 2,
            ];
            if (isset($data['gender'])) {
                $gender = $data['gender'];
                if ($gender === null || $gender === '') {
                    $data['gender'] = null;
                } elseif (array_key_exists($gender, $genderMap)) {
                    $data['gender'] = $genderMap[$gender];
                }
            } else {
                $data['gender'] = null;
            }

            $this->userRepository->createUser($data);

            return back()->with('success', 'Thêm người dùng mới thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }

    public function edit($id)
    {
        $user = $this->userRepository->findById($id)->load('roles');
        if (! $user) {
            return redirect()->route('admin.users.list')->with('error', 'Người dùng không tồn tại');
        }

        $roles = $this->roleRepository->getAllForSelect();
        return view('admin.modules.user.edit', compact('user', 'roles'));
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $data = $request->validated();

            // Kiểm tra email đã verified thì không cho phép thay đổi
            $user = $this->userRepository->findById($id);
            if ($user && $user->email_verified_at && isset($data['email']) && $data['email'] !== $user->email) {
                return back()->with('error', 'Email đã được xác thực không thể thay đổi');
            }

            // Chuẩn hóa giới tính tương tự như khi tạo mới
            $genderMap = [
                'male' => 0,
                'female' => 1,
                'other' => 2,
            ];
            if (isset($data['gender'])) {
                $gender = $data['gender'];
                if ($gender === null || $gender === '') {
                    $data['gender'] = null;
                } elseif (array_key_exists($gender, $genderMap)) {
                    $data['gender'] = $genderMap[$gender];
                }
            } else {
                $data['gender'] = null;
            }

            $user = $this->userRepository->updateUser($id, $data);

            if (! $user) {
                return back()->with('error', 'Người dùng không tồn tại');
            }

            return back()->with('success', 'Cập nhật người dùng thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }

    public function destroy($id)
    {
        try {
            $user = $this->userRepository->findById($id);
            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Người dùng không tồn tại',
                ], 404);
            }

            $this->userRepository->delete($id);

            return response()->json([
                'status' => true,
                'message' => 'Tài khoản đã được ngừng hoạt động',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa người dùng',
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $user = \App\Models\User::withTrashed()->find($id);
            if (! $user || ! $user->trashed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tài khoản không tồn tại hoặc đang hoạt động',
                ], 404);
            }

            $this->userRepository->restore($id);

            return response()->json([
                'status' => true,
                'message' => 'Đã kích hoạt lại tài khoản thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục người dùng',
            ], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $user = \App\Models\User::withTrashed()->find($id);
            if (! $user || ! $user->trashed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tài khoản không tồn tại hoặc đang ngừng hoạt động',
                ], 404);
            }

            $this->userRepository->forceDelete($id);

            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn người dùng thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn người dùng',
            ], 500);
        }
    }

    public function bulkRestore(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || ! is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một người dùng',
                ], 400);
            }

            $count = $this->userRepository->bulkRestore($ids);

            return response()->json([
                'status' => true,
                'message' => "Đã kích hoạt lại {$count} tài khoản thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục',
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || ! is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một người dùng',
                ], 400);
            }

            $count = $this->userRepository->bulkDelete($ids);

            return response()->json([
                'status' => true,
                'message' => "Đã ngừng hoạt động {$count} tài khoản",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa',
            ], 500);
        }
    }

    public function bulkForceDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || ! is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một người dùng',
                ], 400);
            }

            $count = $this->userRepository->bulkForceDelete($ids);

            return response()->json([
                'status' => true,
                'message' => "Đã xóa vĩnh viễn {$count} người dùng thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn',
            ], 500);
        }
    }
}
