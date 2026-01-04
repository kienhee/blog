<div class="d-inline-block text-nowrap">
    @can('account.update')
    <button type="button" class="btn btn-sm btn-icon text-warning btn-edit" title="Chỉnh sửa"
        data-account-id="{{ $account->id }}">
        <i class="bx bx-edit"></i>
    </button>
    @endcan
    @can('account.delete')
    <button type="button" class="btn btn-sm btn-icon text-danger btn-delete" title="Xóa"
        data-url="{{ route('admin.accounts.destroy', $account->id) }}" 
        data-title="{{ $account->name }}">
        <i class="bx bx-trash"></i>
    </button>
    @endcan
</div>

