<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Finance\GetYearByNumberRequest;
use App\Http\Requests\Admin\Finance\StoreYearRequest;
use App\Http\Requests\Admin\Finance\UpdateNoteRequest;
use App\Http\Requests\Admin\Finance\UpdateTargetRequest;
use App\Repositories\FinanceYearRepository;
use Illuminate\Http\Request;

class FinanceYearController extends Controller
{
    protected $financeYearRepository;

    public function __construct(FinanceYearRepository $financeYearRepository)
    {
        $this->financeYearRepository = $financeYearRepository;
    }

    /**
     * Display list of finance years
     */
    public function list()
    {
        $years = $this->financeYearRepository->getYearsByUser();
        
        return view('admin.modules.finance.list', compact('years'));
    }

    /**
     * Store a new finance year
     */
    public function store(StoreYearRequest $request)
    {
        try {
            $year = $this->financeYearRepository->create([
                'year' => $request->input('year'),
                'target' => [],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Tạo năm mới thành công',
                'data' => $year,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi tạo năm',
            ], 500);
        }
    }

    /**
     * Show finance year details
     */
    public function show($id)
    {
        $year = $this->financeYearRepository->findByIdAndUser($id);
        
        // Initialize target if null
        if (is_null($year->target)) {
            $year->target = [];
        }
        
        // Lấy danh sách các tháng đã có
        $existingMonths = $year->financeMonths()->pluck('month')->toArray();
        
        // Tính toán các tháng có thể tạo
        $now = \Carbon\Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;
        $creatableMonths = [];
        
        if ($year->year < $currentYear) {
            // Năm quá khứ: cho phép tạo tất cả tháng
            $creatableMonths = range(1, 12);
        } elseif ($year->year == $currentYear) {
            // Cùng năm: chỉ cho phép tạo tháng <= tháng hiện tại
            $creatableMonths = range(1, $currentMonth);
        } else {
            // Năm tương lai: không cho phép tạo tháng nào
            $creatableMonths = [];
        }
        
        return view('admin.modules.finance.show', compact('year', 'existingMonths', 'creatableMonths'));
    }

    /**
     * Update target (AJAX)
     */
    public function updateTarget(UpdateTargetRequest $request, $id)
    {
        try {
            $target = $request->input('target', []);
            $this->financeYearRepository->updateTarget($id, $target);

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật mục tiêu thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật',
            ], 500);
        }
    }

    /**
     * Get year by year number (find or create)
     */
    public function getYearByNumber(GetYearByNumberRequest $request)
    {
        // Kiểm tra: chỉ cho phép tạo năm >= 2026
        if ($request->input('year') < 2026) {
            return response()->json([
                'status' => false,
                'message' => 'Năm phải lớn hơn hoặc bằng 2026',
            ], 422);
        }

        $year = $this->financeYearRepository->firstOrCreate(
            [
                'year' => $request->input('year'),
            ],
            [
                'target' => [],
            ]
        );

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $year->id,
                'year' => $year->year,
            ],
        ]);
    }

    /**
     * Update note (AJAX)
     */
    public function updateNote(UpdateNoteRequest $request, $id)
    {
        try {
            $this->financeYearRepository->updateNote($id, $request->input('note'));

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật ghi chú thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật',
            ], 500);
        }
    }
}
