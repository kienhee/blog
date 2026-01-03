<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceDay;
use App\Models\FinanceMonth;
use App\Models\FinanceType;
use App\Models\FinanceYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceMonthController extends Controller
{
    /**
     * Remove format from number string (remove dots)
     */
    private function unformatNumber($value)
    {
        if (is_null($value)) {
            return null;
        }
        
        // Convert to string and remove all dots
        return (int) str_replace('.', '', (string) $value);
    }

    /**
     * Show or create finance month
     */
    public function show($yearId, $month)
    {
        // Validate month
        if ($month < 1 || $month > 12) {
            abort(404, 'Tháng không hợp lệ');
        }

        $year = FinanceYear::where('user_id', Auth::id())
            ->findOrFail($yearId);

        // Tìm hoặc tạo tháng
        $financeMonth = FinanceMonth::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'year_id' => $yearId,
                'month' => $month,
            ],
            [
                'total_money' => 0,
                'fix_money' => 0,
                'invest_money' => 0,
                'remaining_money' => 0,
                'note' => [],
            ]
        );

        // Tên tháng
        $monthNames = [
            1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3', 4 => 'Tháng 4',
            5 => 'Tháng 5', 6 => 'Tháng 6', 7 => 'Tháng 7', 8 => 'Tháng 8',
            9 => 'Tháng 9', 10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12',
        ];

        // Lấy danh sách loại chi tiêu
        $financeTypes = FinanceType::orderBy('name')->get();

        // Lấy danh sách các ngày đã có chi tiêu trong tháng
        $financeDays = FinanceDay::where('month_id', $financeMonth->id)
            ->with('financeType')
            ->orderBy('date', 'asc')
            ->get();

        // Tính tổng chi phí trong tháng (tổng số tiền trong tháng)
        $totalExpenses = $financeDays->sum('money');
        
        // Tính lại remaining_money = total_money - tổng chi phí
        $financeMonth->remaining_money = $financeMonth->total_money - $totalExpenses;
        
        // Lưu lại vào database để đảm bảo dữ liệu luôn đúng
        $financeMonth->save();

        return view('admin.modules.finance.month.show', compact('financeMonth', 'year', 'monthNames', 'financeTypes', 'financeDays', 'totalExpenses'));
    }

    /**
     * Get finance days for AJAX requests
     */
    public function getExpenses($monthId)
    {
        $financeMonth = FinanceMonth::where('user_id', Auth::id())
            ->findOrFail($monthId);

        $financeDays = FinanceDay::where('month_id', $monthId)
            ->with('financeType')
            ->orderBy('date', 'asc')
            ->get();

        $events = $financeDays->map(function($day) {
            return [
                'id' => $day->id,
                'title' => ($day->financeType->name ?? 'Chưa phân loại') . ': ' . number_format($day->money) . 'đ',
                'start' => $day->date->format('Y-m-d'),
                'extendedProps' => [
                    'finance_type_id' => $day->finance_type_id,
                    'finance_type_name' => $day->financeType->name ?? 'Chưa phân loại',
                    'money' => $day->money,
                    'note' => $day->note ?? '',
                ],
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $events,
        ]);
    }

    /**
     * Store a new finance day (expense)
     */
    public function storeExpense(Request $request, $monthId)
    {
        $request->validate([
            'date' => 'required|date',
            'finance_type_id' => 'required|exists:finance_type,id',
            'money' => 'required|string', // Accept string first (may contain dots)
            'note' => 'nullable|string|max:255',
        ], [
            'date.required' => 'Vui lòng chọn ngày',
            'date.date' => 'Ngày không hợp lệ',
            'finance_type_id.required' => 'Vui lòng chọn loại chi tiêu',
            'finance_type_id.exists' => 'Loại chi tiêu không tồn tại',
            'money.required' => 'Vui lòng nhập số tiền',
        ]);

        try {
            // Lấy date từ request và parse để lấy year và month
            $date = \Carbon\Carbon::parse($request->input('date'));
            $expenseYear = $date->year;
            $expenseMonth = $date->month;
            
            // Tìm hoặc tạo FinanceYear cho năm của expense
            $financeYear = FinanceYear::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'year' => $expenseYear,
                ],
                [
                    'target' => [],
                    'note' => null,
                ]
            );
            
            // Tìm hoặc tạo FinanceMonth cho tháng của expense (dựa trên date, không phải monthId từ route)
            $financeMonth = FinanceMonth::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'year_id' => $financeYear->id,
                    'month' => $expenseMonth,
                ],
                [
                    'total_money' => 0,
                    'fix_money' => 0,
                    'invest_money' => 0,
                    'remaining_money' => 0,
                    'note' => [],
                ]
            );
            
            // Unformat money (remove dots)
            $money = $this->unformatNumber($request->input('money'));
            
            // Validate money after unformatting
            if ($money < 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Số tiền phải lớn hơn hoặc bằng 0',
                ], 422);
            }
            
            $financeDay = FinanceDay::create([
                'user_id' => Auth::id(),
                'month_id' => $financeMonth->id, // Sử dụng month_id từ date, không phải từ route
                'date' => $request->input('date'),
                'finance_type_id' => $request->input('finance_type_id'),
                'money' => $money,
                'note' => $request->input('note'),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Thêm chi tiêu thành công',
                'data' => $financeDay,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi thêm chi tiêu',
            ], 500);
        }
    }

    /**
     * Update a finance day (expense)
     */
    public function updateExpense(Request $request, $monthId, $id)
    {
        // Tìm expense theo id và user_id (không cần kiểm tra month_id từ route)
        $financeDay = FinanceDay::where('user_id', Auth::id())
            ->findOrFail($id);

        $request->validate([
            'date' => 'required|date',
            'finance_type_id' => 'required|exists:finance_type,id',
            'money' => 'required|string', // Accept string first (may contain dots)
            'note' => 'nullable|string|max:255',
        ], [
            'date.required' => 'Vui lòng chọn ngày',
            'date.date' => 'Ngày không hợp lệ',
            'finance_type_id.required' => 'Vui lòng chọn loại chi tiêu',
            'finance_type_id.exists' => 'Loại chi tiêu không tồn tại',
            'money.required' => 'Vui lòng nhập số tiền',
        ]);

        try {
            // Lấy date từ request và parse để lấy year và month
            $date = \Carbon\Carbon::parse($request->input('date'));
            $expenseYear = $date->year;
            $expenseMonth = $date->month;
            
            // Tìm hoặc tạo FinanceYear cho năm của expense
            $financeYear = FinanceYear::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'year' => $expenseYear,
                ],
                [
                    'target' => [],
                    'note' => null,
                ]
            );
            
            // Tìm hoặc tạo FinanceMonth cho tháng của expense (dựa trên date, không phải monthId từ route)
            $financeMonth = FinanceMonth::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'year_id' => $financeYear->id,
                    'month' => $expenseMonth,
                ],
                [
                    'total_money' => 0,
                    'fix_money' => 0,
                    'invest_money' => 0,
                    'remaining_money' => 0,
                    'note' => [],
                ]
            );
            
            // Unformat money (remove dots)
            $money = $this->unformatNumber($request->input('money'));
            
            // Validate money after unformatting
            if ($money < 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Số tiền phải lớn hơn hoặc bằng 0',
                ], 422);
            }
            
            $financeDay->update([
                'month_id' => $financeMonth->id, // Cập nhật month_id dựa trên date mới
                'date' => $request->input('date'),
                'finance_type_id' => $request->input('finance_type_id'),
                'money' => $money,
                'note' => $request->input('note'),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật chi tiêu thành công',
                'data' => $financeDay,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật chi tiêu',
            ], 500);
        }
    }

    /**
     * Delete a finance day (expense)
     */
    public function destroyExpense($monthId, $id)
    {
        $financeDay = FinanceDay::where('month_id', $monthId)
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        try {
            $financeDay->delete();

            return response()->json([
                'status' => true,
                'message' => 'Xóa chi tiêu thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa chi tiêu',
            ], 500);
        }
    }

    /**
     * Update finance month information
     */
    public function update(Request $request, $monthId)
    {
        $financeMonth = FinanceMonth::where('user_id', Auth::id())
            ->findOrFail($monthId);

        $request->validate([
            'total_money' => 'nullable|string', // Accept string first (may contain dots)
            'fix_money' => 'nullable|string',
            'invest_money' => 'nullable|string',
        ]);

        try {
            // Cập nhật các trường (unformat trước)
            if ($request->has('total_money')) {
                $financeMonth->total_money = $this->unformatNumber($request->input('total_money', 0));
            }
            if ($request->has('fix_money')) {
                $financeMonth->fix_money = $this->unformatNumber($request->input('fix_money', 0));
            }
            if ($request->has('invest_money')) {
                $financeMonth->invest_money = $this->unformatNumber($request->input('invest_money', 0));
            }

            // Tính tổng chi phí trong tháng (tổng từ finance_days)
            $totalExpenses = FinanceDay::where('month_id', $monthId)->sum('money');
            
            // Tính remaining_money = total_money - tổng chi phí
            $financeMonth->remaining_money = $financeMonth->total_money - $totalExpenses;
            
            $financeMonth->save();

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thông tin tháng thành công',
                'data' => [
                    'total_money' => $financeMonth->total_money,
                    'fix_money' => $financeMonth->fix_money,
                    'invest_money' => $financeMonth->invest_money,
                    'remaining_money' => $financeMonth->remaining_money,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật',
            ], 500);
        }
    }
}
