<?php

namespace App\Repositories;

use App\Models\Newsletter;
use Yajra\DataTables\Facades\DataTables;

class NewsletterRepository extends BaseRepository
{
    public function __construct(Newsletter $model)
    {
        parent::__construct($model);
    }

    const STATUS_ACTIVE = 1;

    const STATUS_INACTIVE = 0;

    public function getStatusLabel()
    {
        return [
            self::STATUS_ACTIVE => 'Đã đăng ký',
            self::STATUS_INACTIVE => 'Đã hủy',
        ];
    }

    public function gridData()
    {
        $query = Newsletter::query();
        $query->select([
            'id',
            'email',
            'status',
            'subscribed_at',
            'created_at',
        ]);

        return $query;
    }

    public function filterData($grid)
    {
        $request = request();
        $email = $request->input('email');
        $status = $request->input('status');

        if ($email) {
            $grid->where('email', 'like', '%'.$email.'%');
        }

        if ($status !== null && $status !== '') {
            $grid->where('status', $status);
        }

        // Sắp xếp theo thời gian đăng ký mới nhất
        $grid->orderBy('created_at', 'desc');

        return $grid;
    }

    public function renderDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('email', function ($row) {
                return htmlspecialchars($row->email);
            })
            ->addColumn('status', function ($row) {
                $labels = $this->getStatusLabel();
                $status = $row->status;
                $label = isset($labels[$status]) ? $labels[$status] : 'Không xác định';
                $class = [
                    self::STATUS_ACTIVE => 'bg-label-success',
                    self::STATUS_INACTIVE => 'bg-label-secondary',
                ];
                $badgeClass = isset($class[$status]) ? $class[$status] : 'bg-label-secondary';

                return '<span class="badge '.$badgeClass.'">'.$label.'</span>';
            })
            ->addColumn('subscribed_at', function ($row) {
                if ($row->subscribed_at) {
                    return '<span class="text-muted">'.$row->subscribed_at->format('d/m/Y H:i').'</span>';
                }

                return '<span class="text-muted">-</span>';
            })
            ->addColumn('created_at', function ($row) {
                return '<span class="text-muted">'.$row->created_at->format('d/m/Y H:i').'</span>';
            })
            ->addColumn('action', function ($row) {
                $canDelete = auth()->user()->can('newsletter.delete');

                $html = '<div class="d-inline-block text-nowrap">';

                if ($canDelete) {
                    $html .= '<button type="button"
                            class="btn btn-sm btn-icon delete-item"
                            data-id="'.$row->id.'"
                            data-url="'.route('admin.newsletters.destroy', $row->id).'"
                            title="Xóa">
                        <i class="bx bx-trash"></i>
                    </button>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['status', 'subscribed_at', 'created_at', 'action'])
            ->make(true);
    }
}

