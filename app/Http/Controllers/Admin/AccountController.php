<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class AccountController extends Controller
{
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
        $accounts = Account::where('user_id', Auth::id())
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return DataTables::of($accounts)
            ->addIndexColumn()
            ->addColumn('checkbox_html', function ($account) {
                if (auth()->user()->can('account.delete')) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $account->id . '" />';
                }
                return '';
            })
            ->addColumn('name_html', function ($account) {
                return '<span class="account-name">' . e($account->name) . '</span>';
            })
            ->addColumn('type_html', function ($account) {
                $type = $account->type ?? '-';
                if ($type === '-') {
                    return '<span class="text-muted">-</span>';
                }
                return '<span class="badge rounded-pill bg-label-info d-inline-flex align-items-center lh-1"><span class="badge badge-dot text-bg-info me-1"></span>' . e($type) . '</span>';
            })
            ->addColumn('password_html', function ($account) {
                return '<button type="button" class="btn btn-sm btn-link p-0 text-primary btn-view-password" data-account-id="' . $account->id . '" title="Xem mật khẩu">
                    <i class="bx bx-show me-1"></i><span class="password-display">••••••••</span>
                </button>';
            })
            ->addColumn('note_html', function ($account) {
                return e($account->note ?? '-');
            })
            ->addColumn('created_at_html', function ($account) {
                return '<span class="text-muted">' . $account->created_at->format('d/m/Y H:i') . '</span>';
            })
            ->addColumn('action_html', function ($account) {
                return view('admin.modules.account.partials.action', compact('account'))->render();
            })
            ->rawColumns(['checkbox_html', 'name_html', 'type_html', 'password_html', 'note_html', 'created_at_html', 'action_html'])
            ->make(true);
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
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        try {
            // Lấy order lớn nhất + 1
            $maxOrder = Account::where('user_id', Auth::id())->max('order') ?? 0;
            
            $data = [
                'user_id' => Auth::id(),
                'type' => $request->input('type'),
                'name' => $request->input('name'),
                'note' => $request->input('note'),
                'order' => $maxOrder + 1,
            ];
            
            // Chỉ set password nếu có giá trị (sẽ được encrypt tự động qua mutator)
            if ($request->filled('password')) {
                $data['password'] = $request->input('password');
            }
            
            $account = Account::create($data);

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Thêm tài khoản thành công',
                    'data' => $account,
                ]);
            }

            return redirect()->route('admin.accounts.list')->with('success', 'Thêm tài khoản thành công');
        } catch (\Exception $e) {
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
        $account = Account::where('user_id', Auth::id())->findOrFail($id);
        
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
    public function update(Request $request, $id)
    {
        $account = Account::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'type' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

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

            $account->update($data);

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Cập nhật tài khoản thành công',
                    'data' => $account,
                ]);
            }

            return redirect()->route('admin.accounts.list')->with('success', 'Cập nhật tài khoản thành công');
        } catch (\Exception $e) {
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
        $account = Account::where('user_id', Auth::id())->findOrFail($id);

        try {
            $account->delete();
            return response()->json([
                'status' => true,
                'message' => 'Xóa tài khoản thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa tài khoản',
            ], 500);
        }
    }

    /**
     * Bulk delete accounts
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:accounts,id',
        ]);

        try {
            Account::where('user_id', Auth::id())
                ->whereIn('id', $request->input('ids'))
                ->delete();

            return response()->json([
                'status' => true,
                'message' => 'Xóa tài khoản thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa tài khoản',
            ], 500);
        }
    }

    /**
     * Update order of accounts (for drag & drop)
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:accounts,id',
            'orders.*.order' => 'required|integer',
        ]);

        try {
            foreach ($request->input('orders') as $item) {
                Account::where('user_id', Auth::id())
                    ->where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thứ tự thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thứ tự',
            ], 500);
        }
    }

    /**
     * Verify user password and get account password
     */
    public function viewPassword(Request $request, $id)
    {
        $request->validate([
            'user_password' => 'required|string',
        ], [
            'user_password.required' => 'Vui lòng nhập mật khẩu đăng nhập',
        ]);

        // Verify user's login password
        if (!Hash::check($request->input('user_password'), Auth::user()->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Mật khẩu đăng nhập không chính xác',
            ], 422);
        }

        $account = Account::where('user_id', Auth::id())->findOrFail($id);
        
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
