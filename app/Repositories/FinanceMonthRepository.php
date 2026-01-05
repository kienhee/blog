<?php

namespace App\Repositories;

use App\Models\FinanceDay;
use App\Models\FinanceMonth;
use App\Models\FinanceType;
use App\Models\FinanceYear;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceMonthRepository
{
    protected $model;
    protected $financeDayModel;
    protected $financeYearModel;
    protected $financeTypeModel;

    public function __construct(
        FinanceMonth $model,
        FinanceDay $financeDayModel,
        FinanceYear $financeYearModel,
        FinanceType $financeTypeModel
    ) {
        $this->model = $model;
        $this->financeDayModel = $financeDayModel;
        $this->financeYearModel = $financeYearModel;
        $this->financeTypeModel = $financeTypeModel;
    }

    /**
     * Tìm tháng theo ID và user_id
     */
    public function findByIdAndUser($id)
    {
        return $this->model::where('user_id', Auth::id())
            ->findOrFail($id);
    }

    /**
     * Tìm tháng theo year_id, month và user_id
     */
    public function findByYearAndMonth($yearId, $month)
    {
        return $this->model::where('user_id', Auth::id())
            ->where('year_id', $yearId)
            ->where('month', $month)
            ->first();
    }

    /**
     * Tạo tháng mới
     */
    public function create(array $data)
    {
        $data['user_id'] = Auth::id();
        return $this->model::create($data);
    }

    /**
     * Tìm hoặc tạo tháng
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        $attributes['user_id'] = Auth::id();
        return $this->model::firstOrCreate($attributes, $values);
    }

    /**
     * Cập nhật tháng
     */
    public function update($id, array $data)
    {
        $month = $this->findByIdAndUser($id);
        $month->update($data);
        return $month;
    }

    /**
     * Cập nhật total_money và tính lại remaining_money
     */
    public function updateTotalMoney($id, $totalMoney)
    {
        $month = $this->findByIdAndUser($id);
        $month->total_money = $totalMoney;
        
        // Tính tổng chi phí trong tháng
        $totalExpenses = $this->financeDayModel::where('month_id', $id)->sum('money');
        
        // Tính remaining_money = total_money - tổng chi phí
        $month->remaining_money = $month->total_money - $totalExpenses;
        $month->save();
        
        return $month;
    }

    /**
     * Lấy danh sách finance days của tháng
     */
    public function getFinanceDays($monthId)
    {
        return $this->financeDayModel::where('month_id', $monthId)
            ->with('financeType')
            ->orderBy('date', 'asc')
            ->get();
    }

    /**
     * Lấy danh sách finance types
     */
    public function getFinanceTypes()
    {
        return $this->financeTypeModel::orderBy('name')->get();
    }

    /**
     * Tính lại remaining_money cho tháng
     */
    public function recalculateRemainingMoney($monthId)
    {
        $month = $this->findByIdAndUser($monthId);
        $totalExpenses = $this->financeDayModel::where('month_id', $monthId)->sum('money');
        $month->remaining_money = $month->total_money - $totalExpenses;
        $month->save();
        return $month;
    }

    /**
     * Khóa tháng
     */
    public function lock($id)
    {
        return DB::transaction(function () use ($id) {
            $month = $this->findByIdAndUser($id);
            $month->refresh(); // Refresh để tránh race condition
            
            if ($month->isLocked()) {
                throw new \Exception('Tháng này đã được khóa');
            }
            
            $month->locked_time = now();
            $month->save();
            
            return $month;
        });
    }

    /**
     * Tự động khóa tháng nếu đã qua
     */
    public function autoLockIfPast($month)
    {
        $monthDate = \Carbon\Carbon::create($month->financeYear->year, $month->month, 1)->endOfMonth();
        
        if ($monthDate->isPast() && !$month->isLocked()) {
            DB::transaction(function () use ($month) {
                $month->refresh();
                if (!$month->isLocked()) {
                    $month->locked_time = now();
                    $month->save();
                }
            });
        }
    }
}

