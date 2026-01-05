<?php

namespace App\Repositories;

use App\Models\FinanceDay;
use App\Models\FinanceMonth;
use App\Models\FinanceYear;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceDayRepository
{
    protected $model;
    protected $financeMonthModel;
    protected $financeYearModel;

    public function __construct(
        FinanceDay $model,
        FinanceMonth $financeMonthModel,
        FinanceYear $financeYearModel
    ) {
        $this->model = $model;
        $this->financeMonthModel = $financeMonthModel;
        $this->financeYearModel = $financeYearModel;
    }

    /**
     * Tìm expense theo ID và user_id
     */
    public function findByIdAndUser($id)
    {
        return $this->model::where('user_id', Auth::id())
            ->findOrFail($id);
    }

    /**
     * Tìm expense theo month_id và user_id
     */
    public function findByMonthIdAndUser($monthId, $id)
    {
        return $this->model::where('month_id', $monthId)
            ->where('user_id', Auth::id())
            ->findOrFail($id);
    }

    /**
     * Tạo expense mới
     */
    public function create(array $data)
    {
        $data['user_id'] = Auth::id();
        return $this->model::create($data);
    }

    /**
     * Cập nhật expense
     */
    public function update($id, array $data)
    {
        $expense = $this->findByIdAndUser($id);
        $expense->update($data);
        return $expense;
    }

    /**
     * Xóa expense
     */
    public function delete($id)
    {
        $expense = $this->findByIdAndUser($id);
        return $expense->delete();
    }

    /**
     * Tìm hoặc tạo FinanceYear từ năm
     */
    public function findOrCreateYear($year)
    {
        return $this->financeYearModel::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'year' => $year,
            ],
            [
                'target' => [],
                'note' => null,
            ]
        );
    }

    /**
     * Tìm hoặc tạo FinanceMonth từ year_id và month
     */
    public function findOrCreateMonth($yearId, $month)
    {
        return $this->financeMonthModel::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'year_id' => $yearId,
                'month' => $month,
            ],
            [
                'total_money' => 0,
                'remaining_money' => 0,
                'note' => [],
            ]
        );
    }

    /**
     * Tính lại remaining_money cho nhiều tháng
     */
    public function recalculateRemainingMoneyForMonths(array $monthIds)
    {
        foreach ($monthIds as $monthId) {
            $month = $this->financeMonthModel::find($monthId);
            if ($month) {
                $totalExpenses = $this->model::where('month_id', $monthId)->sum('money');
                $month->remaining_money = $month->total_money - $totalExpenses;
                $month->save();
            }
        }
    }
}

