<?php

namespace App\Repositories;

use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function gridData()
    {
        $query = $this->model::query();
        $query->select('*');

        return $query;
    }

    public function filterData($grid)
    {
        $request = request();
        $createdAt = $request->input('created_at');

        if ($createdAt) {
            $date = \DateTime::createFromFormat('d/m/Y', $createdAt);
            $formattedDate = $date ? $date->format('Y-m-d') : null;
            if ($formattedDate) {
                $grid->whereDate('created_at', $formattedDate);
            }
        }

        return $grid;
    }

    public function renderDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('checkbox_html', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="'.$row->id.'" />';
            })
            ->addColumn('name_html', function ($row) {
                $editUrl = route('admin.roles.edit', $row->id);

                return '<a href="'.$editUrl.'" class="text-body fw-bold" title="'.e($row->name).'">'.e($row->name).'</a>';
            })
            ->addColumn('permissions_count_html', function ($row) {
                $count = $row->permissions()->count();

                return '<span class="badge rounded-pill bg-label-info d-inline-flex align-items-center lh-1"><span class="badge badge-dot text-bg-info me-1"></span>'.$count.' quyền</span>';
            })
            ->addColumn('users_count_html', function ($row) {
                $count = $row->users()->count();

                return '<span class="text-body">'.$count.' người dùng</span>';
            })
            ->addColumn('created_at_html', function ($row) {
                $createdAt = $row->created_at;

                return '<span class="text-muted">'.$createdAt->format('d/m/Y H:i').'</span>';
            })
            ->addColumn('action_html', function ($row) {
                $editUrl = route('admin.roles.edit', $row->id);
                $deleteUrl = route('admin.roles.destroy', $row->id);
                $title = $row->name;

                $canEdit = auth()->user()->can('role.update');
                $canDelete = auth()->user()->can('role.delete');

                $html = '<div class="d-inline-block text-nowrap">';
                
                if ($canEdit) {
                    $html .= '<a href="'.$editUrl.'" class="btn btn-sm btn-icon text-warning" title="Chỉnh sửa">
                        <i class="bx bx-edit"></i>
                    </a>';
                }
                
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon text-danger btn-delete" title="Xóa"
                        data-url="'.$deleteUrl.'" data-title="'.htmlspecialchars($title).'">
                        <i class="bx bx-trash"></i>
                    </button>';
                }
                
                $html .= '</div>';

                return $html ?: '<span class="text-muted">—</span>';
            })
            ->rawColumns(['checkbox_html', 'name_html', 'permissions_count_html', 'users_count_html', 'created_at_html', 'action_html'])
            ->make(true);
    }

    public function getAllForSelect()
    {
        return $this->model::select('id', 'name')
            ->orderBy('name')
            ->get();
    }
}
