<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Finance\StoreExpenseRequest;
use App\Http\Requests\Admin\Finance\UpdateExpenseRequest;
use App\Http\Requests\Admin\Finance\UpdateMonthRequest;
use App\Models\FinanceDay;
use App\Models\FinanceMonth;
use App\Models\FinanceType;
use App\Models\FinanceYear;
use App\Repositories\FinanceDayRepository;
use App\Repositories\FinanceMonthRepository;
use App\Repositories\FinanceYearRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceMonthController extends Controller
{
    protected $financeMonthRepository;
    protected $financeDayRepository;
    protected $financeYearRepository;

    public function __construct(
        FinanceMonthRepository $financeMonthRepository,
        FinanceDayRepository $financeDayRepository,
        FinanceYearRepository $financeYearRepository
    ) {
        $this->financeMonthRepository = $financeMonthRepository;
        $this->financeDayRepository = $financeDayRepository;
        $this->financeYearRepository = $financeYearRepository;
    }

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
     * Check if finance month is locked
     */
    private function checkLocked(FinanceMonth $financeMonth)
    {
        if ($financeMonth->isLocked()) {
            return response()->json([
                'status' => false,
                'message' => 'Tháng này đã bị khóa, không thể chỉnh sửa',
            ], 403);
        }
        return null;
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

        $year = $this->financeYearRepository->findByIdAndUser($yearId);

        // Kiểm tra nếu tháng đã tồn tại thì cho xem, nếu chưa tồn tại thì kiểm tra rule
        $financeMonth = $this->financeMonthRepository->findByYearAndMonth($yearId, $month);

        // Nếu tháng chưa tồn tại, kiểm tra rule tạo tháng
        if (!$financeMonth) {
            $now = \Carbon\Carbon::now();
            $currentYear = $now->year;
            $currentMonth = $now->month;

            // Kiểm tra: không cho tạo tháng > tháng hiện tại
            if ($year->year == $currentYear && $month > $currentMonth) {
                abort(403, 'Không thể tạo tháng trong tương lai. Chỉ có thể tạo tháng hiện tại hoặc tháng đã qua.');
            }

            // Kiểm tra: không cho tạo tháng trong năm tương lai
            if ($year->year > $currentYear) {
                abort(403, 'Không thể tạo tháng trong năm tương lai.');
            }

            // Tạo tháng mới
            $financeMonth = $this->financeMonthRepository->create([
                'year_id' => $yearId,
                'month' => $month,
                'total_money' => 0,
                'remaining_money' => 0,
                'note' => [],
            ]);
        }

        // Tên tháng
        $monthNames = [
            1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3', 4 => 'Tháng 4',
            5 => 'Tháng 5', 6 => 'Tháng 6', 7 => 'Tháng 7', 8 => 'Tháng 8',
            9 => 'Tháng 9', 10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12',
        ];

        // Lấy danh sách loại chi tiêu
        $financeTypes = $this->financeMonthRepository->getFinanceTypes();

        // Lấy danh sách các ngày đã có chi tiêu trong tháng
        $financeDays = $this->financeMonthRepository->getFinanceDays($financeMonth->id);

        // Tính tổng chi phí trong tháng (tổng số tiền trong tháng)
        $totalExpenses = $financeDays->sum('money');
        
        // Tính lại remaining_money = total_money - tổng chi phí
        $financeMonth = $this->financeMonthRepository->recalculateRemainingMoney($financeMonth->id);
        
        // Kiểm tra nếu tháng đã qua và chưa lock thì tự động lock
        $this->financeMonthRepository->autoLockIfPast($financeMonth);

        return view('admin.modules.finance.month.show', compact('financeMonth', 'year', 'monthNames', 'financeTypes', 'financeDays', 'totalExpenses'));
    }

    /**
     * Get finance days for AJAX requests
     */
    public function getExpenses($monthId)
    {
        $financeMonth = $this->financeMonthRepository->findByIdAndUser($monthId);

        $financeDays = $this->financeMonthRepository->getFinanceDays($monthId);

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
    public function storeExpense(StoreExpenseRequest $request, $monthId)
    {
        try {
            // Lấy date từ request và parse để lấy year và month
            $date = \Carbon\Carbon::parse($request->input('date'));
            $expenseYear = $date->year;
            $expenseMonth = $date->month;
            
            // Tìm hoặc tạo FinanceYear cho năm của expense
            $financeYear = $this->financeYearRepository->firstOrCreate(
                [
                    'year' => $expenseYear,
                ],
                [
                    'target' => [],
                    'note' => null,
                ]
            );
            
            // Tìm hoặc tạo FinanceMonth cho tháng của expense (dựa trên date, không phải monthId từ route)
            $financeMonth = $this->financeMonthRepository->firstOrCreate(
                [
                    'year_id' => $financeYear->id,
                    'month' => $expenseMonth,
                ],
                [
                    'total_money' => 0,
                    'remaining_money' => 0,
                    'note' => [],
                ]
            );

            // Kiểm tra nếu tháng đã bị lock
            $lockedCheck = $this->checkLocked($financeMonth);
            if ($lockedCheck) {
                return $lockedCheck;
            }
            
            // Unformat money (remove dots)
            $money = $this->unformatNumber($request->input('money'));
            
            // Validate money after unformatting
            if ($money < 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Số tiền phải lớn hơn hoặc bằng 0',
                ], 422);
            }
            
            $financeDay = $this->financeDayRepository->create([
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
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi thêm chi tiêu',
            ], 500);
        }
    }

    /**
     * Update a finance day (expense)
     */
    public function updateExpense(UpdateExpenseRequest $request, $monthId, $id)
    {
        // Tìm expense theo id và user_id (không cần kiểm tra month_id từ route)
        $financeDay = $this->financeDayRepository->findByIdAndUser($id);

        try {
            $oldMonthId = $financeDay->month_id;
            
            DB::transaction(function() use ($request, &$financeDay, $oldMonthId) {
                // Lấy date từ request và parse để lấy year và month
                $date = \Carbon\Carbon::parse($request->input('date'));
                $expenseYear = $date->year;
                $expenseMonth = $date->month;
                
                // Tìm hoặc tạo FinanceYear cho năm của expense
                $financeYear = $this->financeYearRepository->firstOrCreate(
                    [
                        'year' => $expenseYear,
                    ],
                    [
                        'target' => [],
                        'note' => null,
                    ]
                );
                
                // Tìm hoặc tạo FinanceMonth cho tháng của expense (dựa trên date, không phải monthId từ route)
                $financeMonth = $this->financeMonthRepository->firstOrCreate(
                    [
                        'year_id' => $financeYear->id,
                        'month' => $expenseMonth,
                    ],
                    [
                        'total_money' => 0,
                        'remaining_money' => 0,
                        'note' => [],
                    ]
                );

                // Kiểm tra nếu tháng đã bị lock
                if ($financeMonth->isLocked()) {
                    throw new \Exception('Tháng này đã bị khóa, không thể chỉnh sửa');
                }
                
                // Validate: Không cho update expense sang tháng tương lai
                $now = \Carbon\Carbon::now();
                $monthDate = \Carbon\Carbon::create($expenseYear, $expenseMonth, 1)->endOfMonth();
                if ($monthDate->isFuture() && $expenseYear >= $now->year) {
                    throw new \Exception('Không thể chuyển chi tiêu sang tháng trong tương lai');
                }
                
                // Unformat money (remove dots)
                $money = $this->unformatNumber($request->input('money'));
                
                // Validate money after unformatting
                if ($money < 0) {
                    throw new \Exception('Số tiền phải lớn hơn hoặc bằng 0');
                }
                
                // Validate max value (2,147,483,647 for signed integer)
                if ($money > 2147483647) {
                    throw new \Exception('Số tiền vượt quá giới hạn cho phép');
                }
                
                $financeDay->update([
                    'month_id' => $financeMonth->id, // Cập nhật month_id dựa trên date mới
                    'date' => $request->input('date'),
                    'finance_type_id' => $request->input('finance_type_id'),
                    'money' => $money,
                    'note' => $request->input('note'),
                ]);
                
                // Recalculate remaining_money cho cả tháng cũ và tháng mới
                $monthsToUpdate = [$financeMonth->id];
                if ($oldMonthId != $financeMonth->id) {
                    $monthsToUpdate[] = $oldMonthId;
                }
                
                $this->financeDayRepository->recalculateRemainingMoneyForMonths($monthsToUpdate);
            });

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật chi tiêu thành công',
                'data' => $financeDay->fresh(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error updating expense', [
                'error' => $e->getMessage(),
                'expense_id' => $financeDay->id,
                'request_data' => $request->except(['_token']),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Có lỗi xảy ra khi cập nhật chi tiêu',
            ], 500);
        }
    }

    /**
     * Delete a finance day (expense)
     */
    public function destroyExpense($monthId, $id)
    {
        $financeDay = $this->financeDayRepository->findByMonthIdAndUser($monthId, $id);

        $financeMonth = $this->financeMonthRepository->findByIdAndUser($monthId);

        // Kiểm tra nếu tháng đã bị lock
        $lockedCheck = $this->checkLocked($financeMonth);
        if ($lockedCheck) {
            return $lockedCheck;
        }

        try {
            $this->financeDayRepository->delete($id);

            return response()->json([
                'status' => true,
                'message' => 'Xóa chi tiêu thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa chi tiêu',
            ], 500);
        }
    }

    /**
     * Update finance month information
     */
    public function update(UpdateMonthRequest $request, $monthId)
    {
        $financeMonth = $this->financeMonthRepository->findByIdAndUser($monthId);

        // Kiểm tra nếu tháng đã bị lock
        $lockedCheck = $this->checkLocked($financeMonth);
        if ($lockedCheck) {
            return $lockedCheck;
        }

        try {
            // Cập nhật các trường (unformat trước)
            if ($request->has('total_money')) {
                $totalMoney = $this->unformatNumber($request->input('total_money', 0));
                $financeMonth = $this->financeMonthRepository->updateTotalMoney($monthId, $totalMoney);
            }

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật thông tin tháng thành công',
                'data' => [
                    'total_money' => $financeMonth->total_money,
                    'remaining_money' => $financeMonth->remaining_money,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật',
            ], 500);
        }
    }

    /**
     * Show day details (expenses grouped by date)
     */
    public function dayDetails($monthId)
    {
        $financeMonth = $this->financeMonthRepository->findByIdAndUser($monthId);

        $year = $financeMonth->financeYear;

        // Lấy danh sách các ngày có chi tiêu, nhóm theo ngày
        $financeDays = $this->financeMonthRepository->getFinanceDays($monthId);

        // Nhóm theo ngày và tính tổng
        $daysGrouped = $financeDays->groupBy(function($day) {
            return $day->date->format('Y-m-d');
        })->map(function($dayGroup, $date) {
            return [
                'date' => \Carbon\Carbon::parse($date),
                'expenses' => $dayGroup->map(function($day) {
                    return [
                        'id' => $day->id,
                        'finance_type_name' => $day->financeType->name ?? 'Chưa phân loại',
                        'money' => $day->money,
                        'note' => $day->note ?? '',
                    ];
                }),
                'total' => $dayGroup->sum('money'),
            ];
        })->values();

        // Tên tháng
        $monthNames = [
            1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3', 4 => 'Tháng 4',
            5 => 'Tháng 5', 6 => 'Tháng 6', 7 => 'Tháng 7', 8 => 'Tháng 8',
            9 => 'Tháng 9', 10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12',
        ];

        return view('admin.modules.finance.month.dayDetails', compact('financeMonth', 'year', 'monthNames', 'daysGrouped'));
    }

    /**
     * Lock finance month
     */
    public function lock($monthId)
    {
        $financeMonth = $this->financeMonthRepository->findByIdAndUser($monthId);

        // Kiểm tra nếu tháng đã bị lock
        if ($financeMonth->isLocked()) {
            return response()->json([
                'status' => false,
                'message' => 'Tháng này đã được khóa',
            ], 400);
        }

        try {
            $financeMonth = $this->financeMonthRepository->lock($monthId);

            return response()->json([
                'status' => true,
                'message' => 'Đã khóa tháng thành công',
                'data' => [
                    'locked_time' => $financeMonth->fresh()->locked_time->format('d/m/Y H:i'),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error locking finance month', [
                'error' => $e->getMessage(),
                'month_id' => $financeMonth->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Có lỗi xảy ra khi khóa tháng',
            ], 500);
        }
    }
}
