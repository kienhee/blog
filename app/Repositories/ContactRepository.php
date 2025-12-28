<?php

namespace App\Repositories;

use App\Models\Contact;
use Yajra\DataTables\Facades\DataTables;

class ContactRepository extends BaseRepository
{
    public function __construct(Contact $model)
    {
        parent::__construct($model);
    }

    const STATUS_PENDING = 0;        // Chưa xử lý

    const STATUS_CONTACTED = 1;      // Đã liên hệ

    const STATUS_REPLIED = 2;        // Đã trả lời email

    const STATUS_SPAM = 3;           // Spam

    /**
     * Đếm số lượng contact đang pending
     *
     * @return int
     */
    public function countPending()
    {
        return $this->model->where('status', self::STATUS_PENDING)->count();
    }

    public function getStatusLabel()
    {
        return [
            self::STATUS_PENDING => 'Chưa xử lý',
            self::STATUS_CONTACTED => 'Đã liên hệ',
            self::STATUS_REPLIED => 'Đã trả lời email',
            self::STATUS_SPAM => 'Spam',
        ];
    }

    public function gridData()
    {
        $query = Contact::query();
        $query->select([
            'id',
            'full_name',
            'email',
            'phone',
            'subject',
            'message',
            'status',
            'created_at',
        ]);

        return $query;
    }

    public function filterData($grid)
    {
        $request = request();
        $full_name = $request->input('full_name');
        $email = $request->input('email');
        $subject = $request->input('subject');
        $status = $request->input('status');
        $createdAt = $request->input('created_at');

        if ($full_name) {
            $grid->where('full_name', 'like', '%'.$full_name.'%');
        }

        if ($email) {
            $grid->where('email', 'like', '%'.$email.'%');
        }

        if ($subject) {
            $grid->where('subject', 'like', '%'.$subject.'%');
        }

        // Filter theo status
        // Nếu status = '' (Tất cả), không filter - lấy tất cả
        // Nếu status có giá trị (0, 1, 2, 3), filter theo giá trị đó
        // Mặc định HTML đã select status = 0, nên lần đầu load sẽ filter theo STATUS_PENDING
        if ($status !== null && $status !== '') {
            $grid->where('status', $status);
        }
        // Nếu status = '', không filter (lấy tất cả các trạng thái)

        if ($createdAt) {
            // Convert from d/m/Y to Y-m-d
            $date = \DateTime::createFromFormat('d/m/Y', $createdAt);
            $formattedDate = $date ? $date->format('Y-m-d') : null;
            if ($formattedDate) {
                $grid->whereDate('created_at', $formattedDate);
            }
        }

        // Sắp xếp theo thời gian tạo mới nhất
        $grid->orderBy('created_at', 'desc');

        return $grid;
    }

    public function renderDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('full_name', function ($row) {
                return htmlspecialchars($row->full_name);
            })
            ->addColumn('email', function ($row) {
                return htmlspecialchars($row->email);
            })
            ->addColumn('phone', function ($row) {
                return htmlspecialchars($row->phone ?? '-');
            })
            ->addColumn('subject', function ($row) {
                $fullSubject = htmlspecialchars(strip_tags($row->subject));
                $shortSubject = \Illuminate\Support\Str::limit($fullSubject, 30);

                return '<span class="cursor-pointer" title="'.$fullSubject.'">'.$shortSubject.'</span>';
            })
            ->addColumn('message', function ($row) {
                $fullMessage = htmlspecialchars(strip_tags($row->message));
                $shortMessage = \Illuminate\Support\Str::limit($fullMessage, 50);

                return '<span class="cursor-pointer" title="'.$fullMessage.'">'.$shortMessage.'</span>';
            })
            ->addColumn('status', function ($row) {
                $labels = $this->getStatusLabel();
                $status = $row->status;
                $label = isset($labels[$status]) ? $labels[$status] : 'Không xác định';
                $class = [
                    self::STATUS_PENDING => 'bg-label-warning',
                    self::STATUS_CONTACTED => 'bg-label-info',
                    self::STATUS_REPLIED => 'bg-label-success',
                    self::STATUS_SPAM => 'bg-label-danger',
                ];
                $badgeClass = isset($class[$status]) ? $class[$status] : 'bg-label-secondary';

                return '<span class="badge '.$badgeClass.'">'.$label.'</span>';
            })
            ->addColumn('created_at', function ($row) {
                return '<span class="text-muted">'.$row->created_at->format('d/m/Y H:i').'</span>';
            })
            ->addColumn('action', function ($row) {
                $viewUrl = route('admin.contacts.show', $row->id);
                $currentStatus = $row->status;

                $canRead = auth()->user()->can('contact.read');
                $canUpdate = auth()->user()->can('contact.update');

                $html = '<div class="d-inline-block text-nowrap">';

                // View button - mở modal (chỉ hiển thị nếu có quyền read)
                if ($canRead) {
                    $html .= '<button type="button"
                            class="btn btn-sm btn-icon btn-view-contact"
                            data-id="'.$row->id.'"
                            data-url="'.htmlspecialchars($viewUrl).'"
                            title="Xem chi tiết (Modal)">
                        <i class="bx bx-show"></i>
                    </button>';

                    // Link đến page chi tiết (icon mới)
                    $html .= '<a href="'.$viewUrl.'"
                            class="btn btn-sm btn-icon"
                            title="Xem chi tiết (Page)">
                        <i class="bx bx-file"></i>
                    </a>';
                }

                // Change status button - chỉ hiển thị nếu có quyền update
                if ($canUpdate) {
                    $html .= '<button type="button"
                            class="btn btn-sm btn-icon change-status-item"
                            data-id="'.$row->id.'"
                            data-status="'.$currentStatus.'"
                            title="Thay đổi trạng thái">
                        <i class="bx bx-edit"></i>
                    </button>';
                }

                $html .= '</div>';

                return $html ?: '<span class="text-muted">—</span>';
            })
            ->rawColumns(['status', 'subject', 'message', 'created_at', 'action'])
            ->make(true);
    }
}
