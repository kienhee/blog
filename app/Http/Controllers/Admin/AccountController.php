<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Account\BulkDeleteRequest;
use App\Http\Requests\Admin\Account\StoreRequest;
use App\Http\Requests\Admin\Account\UpdateOrderRequest;
use App\Http\Requests\Admin\Account\UpdateRequest;
use App\Http\Requests\Admin\Account\ViewPasswordRequest;
use App\Models\Account;
use App\Repositories\AccountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    protected $accountRepository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    /**
     * Display list of accounts
     */
    public function list()
    {
        return view('admin.modules.account.list');
    }

    /**
     * Get accounts data for DataTables (AJAX)
     */
    public function ajaxGetData()
    {
        $data = $this->accountRepository->gridData();
        $data = $this->accountRepository->filterData($data);
        return $this->accountRepository->renderDataTables($data);
    }

    /**
     * Show create form
     */
    public function create()
    {
        // Redirect to list (CRUD is now handled via offcanvas)
        return redirect()->route('admin.accounts.list');
    }

    /**
     * Store new account
     */
    public function store(StoreRequest $request)
    {
        try {
            $data = [
                'type' => $request->input('type'),
                'name' => $request->input('name'),
                'note' => $request->input('note'),
                'order' => $this->accountRepository->getNextOrder(),
            ];
            
            // Chỉ set password nếu có giá trị (sẽ được encrypt tự động qua mutator)
            if ($request->filled('password')) {
                $data['password'] = $request->input('password');
            }
            
            $account = $this->accountRepository->create($data);

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Thêm tài khoản thành công',
                    'data' => $account,
                ]);
            }

            return redirect()->route('admin.accounts.list')->with('success', 'Thêm tài khoản thành công');
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Có lỗi xảy ra khi thêm tài khoản',
                ], 500);
            }
            return back()->with('error', 'Có lỗi xảy ra khi thêm tài khoản')->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $account = $this->accountRepository->findByIdAndUser($id);
        
        // Nếu là AJAX request, trả về JSON
        if (request()->ajax()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $account->id,
                    'type' => $account->type,
                    'name' => $account->name,
                    'note' => $account->note,
                ],
            ]);
        }
        
        // Redirect to list if not AJAX (CRUD is now handled via offcanvas)
        return redirect()->route('admin.accounts.list');
    }

    /**
     * Update account
     */
    public function update(UpdateRequest $request, $id)
    {
        try {
            $data = [
                'type' => $request->input('type'),
                'name' => $request->input('name'),
                'note' => $request->input('note'),
            ];

            // Chỉ cập nhật password nếu có giá trị mới (sẽ được encrypt tự động qua mutator)
            if ($request->filled('password')) {
                $data['password'] = $request->input('password');
            }

            $account = $this->accountRepository->update($id, $data);

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Cập nhật tài khoản thành công',
                    'data' => $account,
                ]);
            }

            return redirect()->route('admin.accounts.list')->with('success', 'Cập nhật tài khoản thành công');
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật tài khoản',
                ], 500);
            }
            return back()->with('error', 'Có lỗi xảy ra khi cập nhật tài khoản')->withInput();
        }
    }

    /**
     * Delete account
     */
    public function destroy($id)
    {
        try {
            $this->accountRepository->delete($id);
            return response()->json([
                'status' => true,
                'message' => 'Xóa tài khoản thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa tài khoản',
            ], 500);
        }
    }

    /**
     * Bulk delete accounts
     */
    public function bulkDelete(BulkDeleteRequest $request)
    {
        try {
            $ids = $request->input('ids');
            foreach ($ids as $id) {
                $this->accountRepository->delete($id);
            }

            return response()->json([
                'status' => true,
                'message' => 'Xóa tài khoản thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa tài khoản',
            ], 500);
        }
    }

    /**
     * Update order of accounts (for drag & drop)
     */
    public function updateOrder(UpdateOrderRequest $request)
    {
        try {
            $this->accountRepository->updateOrders($request->input('orders'));

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thứ tự thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thứ tự',
            ], 500);
        }
    }

    /**
     * Verify user password and get account password
     */
    public function viewPassword(ViewPasswordRequest $request, $id)
    {
        // Verify user's login password
        if (!Hash::check($request->input('user_password'), Auth::user()->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Mật khẩu đăng nhập không chính xác',
            ], 422);
        }

        $account = $this->accountRepository->findByIdAndUser($id);
        
        // Password sẽ được decrypt tự động qua accessor
        // Cần makeVisible để hiển thị password trong JSON (vì nó nằm trong $hidden)
        $account->makeVisible('password');
        return response()->json([
            'status' => true,
            'password' => $account->password,
        ]);
    }

    /**
     * Generate strong password
     */
    public function generatePassword()
    {
        $length = 16;
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $chars = $uppercase . $lowercase . $numbers . $symbols;
        $password = '';
        
        // Đảm bảo có ít nhất 1 ký tự từ mỗi loại
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $symbols[rand(0, strlen($symbols) - 1)];
        
        // Thêm các ký tự ngẫu nhiên còn lại
        for ($i = 4; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        // Xáo trộn password
        $password = str_shuffle($password);
        
        return response()->json([
            'status' => true,
            'password' => $password,
        ]);
    }
}
