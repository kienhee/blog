<?php

namespace App\Repositories;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    /**
     * Hàm khởi tạo nhận vào một model
     *
     * @param  mixed  $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->findById($id);
        if ($record) {
            $record->update($data);

            return $record;
        }

        return null;
    }

    public function delete($id)
    {
        $record = $this->findById($id);
        if ($record) {
            return $record->delete();
        }

        return false;
    }

    /**
     * Hàm triển khai thực hiện select các cột cần thiết
     */
    abstract public function gridData();

    /**
     * Hàm triển khai filter grid
     *
     * @param  mixed  $grid
     */
    abstract public function filterData($grid);

    /**
     * Hàm render HTML cho thư viện Datatables
     *
     * @param  collection  $data
     */
    abstract public function renderDataTables($data);
}
