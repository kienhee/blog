<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FinanceYearController extends Controller
{
    /**
     * Display list of finance years
     */
    public function list()
    {
        $years = FinanceYear::where('user_id', Auth::id())
            ->orderBy('year', 'desc')
            ->get();
        
        return view('admin.modules.finance.list', compact('years'));
    }

    /**
     * Store a new finance year
     */
    public function store(Request $request)
    {
        $request->validate([
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('finance_years', 'year')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
        ], [
            'year.required' => 'Vui lòng nhập năm',
            'year.integer' => 'Năm phải là số nguyên',
            'year.min' => 'Năm phải lớn hơn hoặc bằng 2000',
            'year.max' => 'Năm phải nhỏ hơn hoặc bằng 2100',
            'year.unique' => 'Năm này đã tồn tại trong hệ thống',
        ]);

        try {
            $year = FinanceYear::create([
                'user_id' => Auth::id(),
                'year' => $request->input('year'),
                'target' => [],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Tạo năm mới thành công',
                'data' => $year,
            ]);
        } catch (\Exception $e) {
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
        $year = FinanceYear::where('user_id', Auth::id())
            ->findOrFail($id);
        
        // Initialize target if null
        if (is_null($year->target)) {
            $year->target = [];
        }
        
        // Lấy danh sách các tháng đã có
        $existingMonths = $year->financeMonths()->pluck('month')->toArray();
        
        return view('admin.modules.finance.show', compact('year', 'existingMonths'));
    }

    /**
     * Update target (AJAX)
     */
    public function updateTarget(Request $request, $id)
    {
        $year = FinanceYear::where('user_id', Auth::id())
            ->findOrFail($id);
        
        $request->validate([
            'target' => 'nullable|array',
            'target.*.name' => 'required_with:target|string|max:255',
            'target.*.completed' => 'boolean',
        ]);

        try {
            $target = $request->input('target', []);
            $year->target = $target;
            $year->save();

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật mục tiêu thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật',
            ], 500);
        }
    }

    /**
     * Get year by year number (find or create)
     */
    public function getYearByNumber(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $year = FinanceYear::firstOrCreate(
            [
                'user_id' => Auth::id(),
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
    public function updateNote(Request $request, $id)
    {
        $year = FinanceYear::where('user_id', Auth::id())
            ->findOrFail($id);
        
        $request->validate([
            'note' => 'nullable|string|max:65535',
        ]);

        try {
            $year->note = $request->input('note');
            $year->save();

            return response()->json([
                'status' => true,
                'message' => 'Cập nhật ghi chú thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật' . $e->getMessage(),
            ], 500);
        }
    }
}
