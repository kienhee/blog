<?php

namespace App\Repositories;

use App\Models\HashTag;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class HashTagRepository extends BaseRepository
{
    public function __construct(HashTag $model)
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
        $search = $request->input('search.value');

        if ($createdAt) {
            // Convert from d/m/Y to Y-m-d
            $date = \DateTime::createFromFormat('d/m/Y', $createdAt);
            $formattedDate = $date ? $date->format('Y-m-d') : null;
            if ($formattedDate) {
                $grid->whereDate('created_at', $formattedDate);
            }
        }

        if ($search) {
            $grid->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
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
                $name = $row->name;
                $editUrl = route('admin.hashtags.edit', $row->id);

                return '
                    <div class="d-flex justify-content-start align-items-center">
                        <div class="d-flex flex-column">
                            <a href="' . $editUrl . '" class="text-body fw-bold text-nowrap mb-0" title="' . $name . '">' . Str::limit($name, 50) . '</a>
                        </div>
                    </div>
                ';
            })
            ->addColumn('slug_html', function ($row) {
                return '<span class="text-muted">' . $row->slug . '</span>';
            })
            ->addColumn('created_at_html', function ($row) {
                $createdAt = $row->created_at;

                return '<span class="text-muted">' . $createdAt->format('d/m/Y H:i') . '</span>';
            })
            ->addColumn('action_html', function ($row) {
                $editUrl = route('admin.hashtags.edit', $row->id);
                $deleteUrl = route('admin.hashtags.destroy', $row->id);
                $title = $row->name;

                $canEdit = auth()->user()->can('hashtag.update');
                $canDelete = auth()->user()->can('hashtag.delete');

                $html = '<div class="d-inline-block text-nowrap">';
                
                if ($canEdit) {
                    $html .= '<a href="' . $editUrl . '" class="btn btn-sm btn-icon text-warning" title="Chỉnh sửa">
                        <i class="bx bx-edit"></i>
                    </a>';
                }
                
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon text-danger btn-delete" title="Xóa"
                        data-url="' . $deleteUrl . '" data-title="' . htmlspecialchars($title) . '">
                        <i class="bx bx-trash"></i>
                    </button>';
                }
                
                $html .= '</div>';

                return $html ?: '<span class="text-muted">—</span>';
            })
            ->rawColumns(['checkbox_html', 'name_html', 'slug_html', 'created_at_html', 'action_html'])
            ->make(true);
    }

    /**
     * Get all hashtags ordered by name
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHashTagByType($type = null)
    {
        return $this->gridData()
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get all active hashtags ordered by name
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActiveHashtags()
    {
        return $this->model->whereNull('deleted_at')
                           ->orderBy('name', 'asc')
                           ->get();
    }

    /**
     * Get top hashtags with most posts (only published and not deleted posts)
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopHashtagsByPostCount($limit = 10)
    {
        return $this->model
            ->select('hash_tags.*')
            ->selectRaw('COUNT(DISTINCT CASE 
                WHEN posts.status = "published" 
                    AND posts.deleted_at IS NULL 
                    AND (posts.scheduled_at IS NULL OR posts.scheduled_at <= NOW())
                THEN post_hashtags.post_id 
                ELSE NULL 
            END) as post_count')
            ->leftJoin('post_hashtags', 'hash_tags.id', '=', 'post_hashtags.hashtag_id')
            ->leftJoin('posts', 'post_hashtags.post_id', '=', 'posts.id')
            ->whereNull('hash_tags.deleted_at')
            ->groupBy('hash_tags.id')
            ->havingRaw('post_count > 0')
            ->orderBy('post_count', 'desc')
            ->orderBy('hash_tags.name', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get trashed data for DataTables
     */
    public function gridTrashedData()
    {
        $query = $this->model::onlyTrashed();
        $query->select('*');
        return $query;
    }

    /**
     * Render DataTables for trashed hashtags
     */
    public function renderTrashedDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name_html', function ($row) {
                $name = $row->name;
                return '<span class="text-body fw-bold">' . Str::limit($name, 50) . '</span>';
            })
            ->addColumn('slug_html', function ($row) {
                return '<span class="text-muted">' . $row->slug . '</span>';
            })
            ->addColumn('deleted_at_html', function ($row) {
                $deletedAt = $row->deleted_at;
                return '<span class="text-muted">' . $deletedAt->format('d/m/Y H:i') . '</span>';
            })
            ->addColumn('checkbox_html', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '" />';
            })
            ->addColumn('action_html', function ($row) {
                $restoreUrl = route('admin.hashtags.restore', $row->id);
                $forceDeleteUrl = route('admin.hashtags.forceDelete', $row->id);
                $title = $row->name;

                $canUpdate = auth()->user()->can('hashtag.update');
                $canDelete = auth()->user()->can('hashtag.delete');

                $html = '<div class="d-inline-block text-nowrap">';
                
                if ($canUpdate) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon btn-success btn-restore" title="Khôi phục"
                        data-url="' . $restoreUrl . '" data-title="' . htmlspecialchars($title) . '">
                        <i class="bx bx-undo"></i>
                    </button>';
                }
                
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon text-danger btn-force-delete" title="Xóa vĩnh viễn"
                        data-url="' . $forceDeleteUrl . '" data-title="' . htmlspecialchars($title) . '">
                        <i class="bx bx-trash"></i>
                    </button>';
                }
                
                $html .= '</div>';

                return $html ?: '<span class="text-muted">—</span>';
            })
            ->rawColumns(['checkbox_html', 'name_html', 'slug_html', 'deleted_at_html', 'action_html'])
            ->make(true);
    }

    /**
     * Restore a trashed hashtag
     */
    public function restore($id)
    {
        $hashtag = $this->model::withTrashed()->find($id);
        if ($hashtag && $hashtag->trashed()) {
            return $hashtag->restore();
        }
        return false;
    }

    /**
     * Force delete a hashtag
     */
    public function forceDelete($id)
    {
        $hashtag = $this->model::withTrashed()->find($id);
        if ($hashtag && $hashtag->trashed()) {
            return $hashtag->forceDelete();
        }
        return false;
    }

    /**
     * Bulk delete hashtags (soft delete)
     */
    public function bulkDelete(array $ids)
    {
        return $this->model::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->delete();
    }

    /**
     * Bulk restore hashtags
     */
    public function bulkRestore(array $ids)
    {
        return $this->model::withTrashed()
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at')
            ->restore();
    }

    /**
     * Bulk force delete hashtags
     */
    public function bulkForceDelete(array $ids)
    {
        return $this->model::withTrashed()
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at')
            ->forceDelete();
    }

    /**
     * Get hashtag by slug
     *
     * @param string $slug
     * @return \App\Models\HashTag|null
     */
    public function getHashTagBySlug($slug)
    {
        return $this->gridData()
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->first();
    }
}
