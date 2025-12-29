<?php

namespace App\Repositories;

use App\Models\Newsletter;
use Illuminate\Support\Facades\Auth;
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
            'scroll_percentage',
            'time_on_page',
            'is_human',
            'spam_score',
        ]);

        return $query;
    }

    public function filterData($grid)
    {
        $request = request();
        $email = $request->input('email');
        $status = $request->input('status');
        $isHuman = $request->input('is_human');

        if ($email) {
            $grid->where('email', 'like', '%'.$email.'%');
        }

        if ($status !== null && $status !== '') {
            $grid->where('status', $status);
        }

        if ($isHuman !== null && $isHuman !== '') {
            $grid->where('is_human', $isHuman);
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
            ->addColumn('is_human', function ($row) {
                if ($row->is_human === null) {
                    return '<span class="text-muted">-</span>';
                }
                
                if ($row->is_human) {
                    return '<span class="badge bg-label-success"><i class="bx bx-user-check me-1"></i>Người</span>';
                } else {
                    return '<span class="badge bg-label-danger"><i class="bx bx-bot me-1"></i>Bot/Spam</span>';
                }
            })
            ->addColumn('spam_score', function ($row) {
                if ($row->spam_score === null) {
                    return '<span class="text-muted">-</span>';
                }
                
                $score = (int) $row->spam_score;
                $badgeClass = 'bg-label-success';
                if ($score >= 70) {
                    $badgeClass = 'bg-label-danger';
                } elseif ($score >= 50) {
                    $badgeClass = 'bg-label-warning';
                } elseif ($score >= 30) {
                    $badgeClass = 'bg-label-info';
                }
                
                return '<span class="badge '.$badgeClass.'">'.$score.'</span>';
            })
            ->addColumn('behavior', function ($row) {
                $html = '<div class="small">';
                
                if ($row->scroll_percentage !== null) {
                    $html .= '<div class="mb-1"><i class="bx bx-down-arrow-alt me-1"></i>Scroll: <strong>'.number_format($row->scroll_percentage, 1).'%</strong></div>';
                }
                
                if ($row->time_on_page !== null) {
                    $html .= '<div><i class="bx bx-time me-1"></i>Thời gian: <strong>'.$row->time_on_page.'s</strong></div>';
                }
                
                if ($row->scroll_percentage === null && $row->time_on_page === null) {
                    $html = '<span class="text-muted">-</span>';
                } else {
                    $html .= '</div>';
                }
                
                return $html;
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
                $user = Auth::user();
                $canDelete = $user && $user->can('newsletter.delete');

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
            ->rawColumns(['status', 'subscribed_at', 'created_at', 'is_human', 'spam_score', 'behavior', 'action'])
            ->make(true);
    }
}

