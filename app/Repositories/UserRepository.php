<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function gridData()
    {
        $query = $this->model::query();
        $query->select('*');
        $query->where('id', '!=', auth()->id());

        return $query;
    }

    public function filterData($grid)
    {
        $request = request();
        $createdAt = $request->input('created_at');
        $verifiedStatus = $request->input('verified_status'); // verified | unverified | null

        if ($createdAt) {
            $date = \DateTime::createFromFormat('d/m/Y', $createdAt);
            $formattedDate = $date ? $date->format('Y-m-d') : null;
            if ($formattedDate) {
                $grid->whereDate('created_at', $formattedDate);
            }
        }

        if ($verifiedStatus === 'verified') {
            $grid->whereNotNull('email_verified_at');
        } elseif ($verifiedStatus === 'unverified') {
            $grid->whereNull('email_verified_at');
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
            ->addColumn('user_html', function ($row) {
                $avatar = thumb_path($row->avatar) ?: asset_shared_url('images/default.png');
                $displayName = $row->full_name ?: $row->email;
                $editUrl = route('admin.users.edit', $row->id);

                return '
                    <div class="d-flex justify-content-start align-items-center">
                        <div class="avatar-wrapper">
                            <div class="avatar avatar-md me-2 rounded-2 bg-label-secondary">
                                <a href="'.$avatar.'" data-lightbox="user-avatars" data-title="'.e($displayName).'">
                                    <img src="'.$avatar.'" alt="'.e($displayName).'" class="rounded-2 img-fluid object-fit-cover">
                                </a>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <a href="'.$editUrl.'" class="text-body fw-bold text-nowrap mb-0" title="'.e($displayName).'">'.e(Str::limit($displayName, 50)).'</a>
                        </div>
                    </div>
                ';
            })
            ->addColumn('email_html', function ($row) {
                return '<span class="text-body">'.e($row->email).'</span>';
            })
            ->addColumn('phone_html', function ($row) {
                return $row->phone ? '<span class="text-body">'.e($row->phone).'</span>' : '<span class="text-muted">—</span>';
            })
            ->addColumn('created_at_html', function ($row) {
                $createdAt = $row->created_at;

                return '<span class="text-muted">'.$createdAt->format('d/m/Y H:i').'</span>';
            })
            ->addColumn('email_verified_html', function ($row) {
                if ($row->email_verified_at) {
                    return '<span class="badge bg-label-success">Đã xác minh</span>';
                }

                return '<span class="badge bg-label-warning">Chưa xác minh</span>';
            })
            ->addColumn('action_html', function ($row) {
                $editUrl = route('admin.users.edit', $row->id);
                $deleteUrl = route('admin.users.destroy', $row->id);
                $title = $row->full_name ?: $row->email;

                return '
                    <div class="d-inline-block text-nowrap">
                        <a href="'.$editUrl.'" class="btn btn-sm btn-icon" title="Chỉnh sửa">
                            <i class="bx bx-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-icon text-danger btn-delete" title="Ngừng hoạt động"
                            data-url="'.$deleteUrl.'" data-title="'.htmlspecialchars($title).'">
                            <i class="bx bx-user-x"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['checkbox_html', 'user_html', 'email_html', 'phone_html', 'created_at_html', 'email_verified_html', 'action_html'])
            ->make(true);
    }

    public function gridTrashedData()
    {
        $query = $this->model::onlyTrashed();
        $query->select('*');

        return $query;
    }

    public function renderTrashedDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('user_html', function ($row) {
                $displayName = $row->full_name ?: $row->email;

                return '<span class="text-body fw-bold">'.e(Str::limit($displayName, 50)).'</span>';
            })
            ->addColumn('email_html', function ($row) {
                return '<span class="text-muted">'.e($row->email).'</span>';
            })
            ->addColumn('deleted_at_html', function ($row) {
                $deletedAt = $row->deleted_at;

                return '<span class="text-muted">'.$deletedAt->format('d/m/Y H:i').'</span>';
            })
            ->addColumn('checkbox_html', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="'.$row->id.'" />';
            })
            ->addColumn('action_html', function ($row) {
                $restoreUrl = route('admin.users.restore', $row->id);
                $forceDeleteUrl = route('admin.users.forceDelete', $row->id);
                $title = $row->full_name ?: $row->email;

                return '
                    <div class="d-inline-block text-nowrap">
                        <button type="button" class="btn btn-sm btn-icon btn-success btn-restore" title="Kích hoạt lại"
                            data-url="'.$restoreUrl.'" data-title="'.htmlspecialchars($title).'">
                            <i class="bx bx-check-circle"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-icon text-danger btn-force-delete" title="Xóa vĩnh viễn"
                            data-url="'.$forceDeleteUrl.'" data-title="'.htmlspecialchars($title).'">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['checkbox_html', 'user_html', 'email_html', 'deleted_at_html', 'checkbox_html', 'action_html'])
            ->make(true);
    }

    public function restore($id)
    {
        $user = $this->model::withTrashed()->find($id);
        if ($user && $user->trashed()) {
            return $user->restore();
        }

        return false;
    }

    public function forceDelete($id)
    {
        $user = $this->model::withTrashed()->find($id);
        if ($user && $user->trashed()) {
            return $user->forceDelete();
        }

        return false;
    }

    public function bulkDelete(array $ids)
    {
        return $this->model::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->delete();
    }

    public function bulkRestore(array $ids)
    {
        return $this->model::withTrashed()
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at')
            ->restore();
    }

    public function bulkForceDelete(array $ids)
    {
        return $this->model::withTrashed()
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at')
            ->forceDelete();
    }

    public function createUser(array $data)
    {
        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->create($data);
    }

    public function updateUser($id, array $data)
    {
        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $this->update($id, $data);
    }
}
