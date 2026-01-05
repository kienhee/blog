<?php

namespace App\Repositories;

use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class AccountRepository extends BaseRepository
{
    public function __construct(Account $model)
    {
        parent::__construct($model);
    }

    /**
     * Query builder cho DataTables
     */
    public function gridData()
    {
        $query = $this->model::query();
        $query->select([
            'accounts.id',
            'accounts.type',
            'accounts.name',
            'accounts.note',
            'accounts.order',
            'accounts.created_at',
        ])
            ->where('accounts.user_id', Auth::id())
            ->orderBy('accounts.order', 'asc')
            ->orderBy('accounts.created_at', 'desc');

        return $query;
    }

    /**
     * Apply filters từ request
     */
    public function filterData($grid)
    {
        // Account không có filters phức tạp, chỉ filter theo user_id
        return $grid;
    }

    /**
     * Render HTML cho DataTables
     */
    public function renderDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('checkbox_html', function ($account) {
                if (auth()->user()->can('account.delete')) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $account->id . '" />';
                }
                return '';
            })
            ->addColumn('name_html', function ($account) {
                return '<span class="account-name">' . e($account->name) . '</span>';
            })
            ->addColumn('type_html', function ($account) {
                $type = $account->type ?? '-';
                if ($type === '-') {
                    return '<span class="text-muted">-</span>';
                }
                return '<span class="badge rounded-pill bg-label-info d-inline-flex align-items-center lh-1"><span class="badge badge-dot text-bg-info me-1"></span>' . e($type) . '</span>';
            })
            ->addColumn('password_html', function ($account) {
                return '<button type="button" class="btn btn-sm btn-link p-0 text-primary btn-view-password" data-account-id="' . $account->id . '" title="Xem mật khẩu">
                    <i class="bx bx-show me-1"></i><span class="password-display">••••••••</span>
                </button>';
            })
            ->addColumn('note_html', function ($account) {
                return e($account->note ?? '-');
            })
            ->addColumn('created_at_html', function ($account) {
                return '<span class="text-muted">' . $account->created_at->format('d/m/Y H:i') . '</span>';
            })
            ->addColumn('action_html', function ($account) {
                return view('admin.modules.account.partials.action', compact('account'))->render();
            })
            ->rawColumns(['checkbox_html', 'name_html', 'type_html', 'password_html', 'note_html', 'created_at_html', 'action_html'])
            ->make(true);
    }

    /**
     * Tìm account theo ID và user_id
     */
    public function findByIdAndUser($id)
    {
        return $this->model::where('user_id', Auth::id())->findOrFail($id);
    }

    /**
     * Lấy order lớn nhất + 1
     */
    public function getNextOrder(): int
    {
        $maxOrder = $this->model::where('user_id', Auth::id())->max('order') ?? 0;
        return $maxOrder + 1;
    }

    /**
     * Cập nhật order cho nhiều accounts
     */
    public function updateOrders(array $orders): void
    {
        foreach ($orders as $item) {
            $this->model::where('user_id', Auth::id())
                ->where('id', $item['id'])
                ->update(['order' => $item['order']]);
        }
    }
}

