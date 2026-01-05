<?php

namespace App\Repositories;

use App\Models\FinanceYear;
use Illuminate\Support\Facades\Auth;

class FinanceYearRepository
{
    protected $model;

    public function __construct(FinanceYear $model)
    {
        $this->model = $model;
    }

    /**
     * Lấy danh sách năm của user
     */
    public function getYearsByUser()
    {
        return $this->model::where('user_id', Auth::id())
            ->orderBy('year', 'desc')
            ->get();
    }

    /**
     * Tìm năm theo ID và user_id
     */
    public function findByIdAndUser($id)
    {
        return $this->model::where('user_id', Auth::id())
            ->findOrFail($id);
    }

    /**
     * Tạo năm mới
     */
    public function create(array $data)
    {
        $data['user_id'] = Auth::id();
        return $this->model::create($data);
    }

    /**
     * Tìm hoặc tạo năm
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        $attributes['user_id'] = Auth::id();
        return $this->model::firstOrCreate($attributes, $values);
    }

    /**
     * Cập nhật năm
     */
    public function update($id, array $data)
    {
        $year = $this->findByIdAndUser($id);
        $year->update($data);
        return $year;
    }

    /**
     * Cập nhật target
     */
    public function updateTarget($id, array $target)
    {
        $year = $this->findByIdAndUser($id);
        $year->target = $target;
        $year->save();
        return $year;
    }

    /**
     * Cập nhật note
     */
    public function updateNote($id, ?string $note)
    {
        $year = $this->findByIdAndUser($id);
        $year->note = $note;
        $year->save();
        return $year;
    }
}

