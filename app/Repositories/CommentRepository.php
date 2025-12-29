<?php

namespace App\Repositories;

use App\Models\Comment;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class CommentRepository extends BaseRepository
{
    public function __construct(Comment $model)
    {
        parent::__construct($model);
    }

    /**
     * Get status labels
     *
     * @return array
     */
    public function getStatusLabel()
    {
        return [
            Comment::STATUS_PENDING => 'Chờ duyệt',
            Comment::STATUS_APPROVED => 'Đã duyệt',
            Comment::STATUS_SPAM => 'Spam',
            Comment::STATUS_TRASH => 'Thùng rác',
        ];
    }

    /**
     * Count pending comments
     *
     * @return int
     */
    public function countPending()
    {
        return $this->model->where('status', Comment::STATUS_PENDING)->count();
    }

    /**
     * Grid data for DataTables
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function gridData()
    {
        $query = $this->model::query();
        $query->select([
            'comments.id',
            'comments.post_id',
            'comments.user_id',
            'comments.content',
            'comments.status',
            'comments.parent_id',
            'comments.created_at',
            'comments.updated_at',
        ])
        ->with(['user', 'post', 'parent']);

        return $query;
    }

    /**
     * Filter data for DataTables
     *
     * @param \Illuminate\Database\Eloquent\Builder $grid
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filterData($grid)
    {
        $request = request();
        $search = $request->input('search.value');
        $status = $request->input('status');
        $createdAt = $request->input('created_at');

        if ($search) {
            $grid->where(function ($query) use ($search) {
                $query->where('comments.content', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('full_name', 'like', '%' . $search . '%')
                          ->orWhere('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('post', function ($q) use ($search) {
                        $q->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($status !== null && $status !== '') {
            $grid->where('comments.status', $status);
        }

        if ($createdAt) {
            $date = \DateTime::createFromFormat('d/m/Y', $createdAt);
            $formattedDate = $date ? $date->format('Y-m-d') : null;
            if ($formattedDate) {
                $grid->whereDate('comments.created_at', $formattedDate);
            }
        }

        $grid->orderBy('comments.created_at', 'desc');

        return $grid;
    }

    /**
     * Render DataTables
     *
     * @param \Illuminate\Database\Eloquent\Builder $data
     * @return mixed
     */
    public function renderDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('checkbox_html', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '" />';
            })
            ->addColumn('user_html', function ($row) {
                $user = $row->user;
                if (!$user) {
                    return '<span class="text-muted">—</span>';
                }

                $name = $user->full_name ?? $user->email;
                $avatar = $user->avatar ? thumb_path($user->avatar) : null;

                $html = '<div class="d-flex align-items-center">';
                if ($avatar) {
                    $html .= '<img src="' . $avatar . '" alt="' . htmlspecialchars($name) . '" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;" />';
                } else {
                    $html .= '<div class="avatar-initial rounded-circle bg-label-primary me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">';
                    $html .= '<span class="text-xs">' . strtoupper(substr($name, 0, 1)) . '</span>';
                    $html .= '</div>';
                }
                $html .= '<div class="d-flex flex-column">';
                $html .= '<span class="text-body fw-semibold">' . htmlspecialchars($name) . '</span>';
                $html .= '<small class="text-muted">' . htmlspecialchars($user->email) . '</small>';
                $html .= '</div>';
                $html .= '</div>';

                return $html;
            })
            ->addColumn('post_html', function ($row) {
                $post = $row->post;
                if (!$post) {
                    return '<span class="text-muted">—</span>';
                }

                $title = Str::limit($post->title, 50);
                $url = route('client.post', ['slug' => $post->slug]);

                return '<a href="' . $url . '" target="_blank" class="text-body fw-semibold" title="' . htmlspecialchars($post->title) . '">' . htmlspecialchars($title) . '</a>';
            })
            ->addColumn('content_html', function ($row) {
                $content = Str::limit(strip_tags($row->content), 100);
                $isReply = $row->parent_id !== null;
                $html = '';

                if ($isReply && $row->parent) {
                    $html .= '<div class="mb-2">';
                    $html .= '<small class="text-muted">Trả lời: <strong>' . htmlspecialchars(Str::limit(strip_tags($row->parent->content), 50)) . '</strong></small>';
                    $html .= '</div>';
                }

                $html .= '<div class="text-body">' . nl2br(htmlspecialchars($content)) . '</div>';

                return $html;
            })
            ->addColumn('status_html', function ($row) {
                $statusLabels = $this->getStatusLabel();
                $statusLabel = $statusLabels[$row->status] ?? $row->status;

                $badgeClass = match ($row->status) {
                    Comment::STATUS_APPROVED => 'bg-label-success',
                    Comment::STATUS_PENDING => 'bg-label-warning',
                    Comment::STATUS_SPAM => 'bg-label-danger',
                    Comment::STATUS_TRASH => 'bg-label-secondary',
                    default => 'bg-label-secondary',
                };

                return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($statusLabel) . '</span>';
            })
            ->addColumn('created_at_html', function ($row) {
                return '<span class="text-muted">' . $row->created_at->format('d/m/Y H:i') . '</span>';
            })
            ->addColumn('action_html', function ($row) {
                $showUrl = route('admin.comments.show', $row->id);
                $deleteUrl = route('admin.comments.destroy', $row->id);
                $approveUrl = route('admin.comments.changeStatus', [$row->id, Comment::STATUS_APPROVED]);
                $spamUrl = route('admin.comments.changeStatus', [$row->id, Comment::STATUS_SPAM]);
                $trashUrl = route('admin.comments.changeStatus', [$row->id, Comment::STATUS_TRASH]);

                $canUpdate = auth()->user()->can('comment.update');
                $canDelete = auth()->user()->can('comment.delete');

                $html = '<div class="d-inline-block text-nowrap">';

                if ($canUpdate) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon text-primary btn-show-comment" title="Xem chi tiết" data-url="' . $showUrl . '">';
                    $html .= '<i class="bx bx-show"></i>';
                    $html .= '</button>';

                    if ($row->status !== Comment::STATUS_APPROVED) {
                        $html .= '<button type="button" class="btn btn-sm btn-icon text-success btn-approve-comment" title="Duyệt" data-url="' . $approveUrl . '">';
                        $html .= '<i class="bx bx-check"></i>';
                        $html .= '</button>';
                    }

                    if ($row->status !== Comment::STATUS_SPAM) {
                        $html .= '<button type="button" class="btn btn-sm btn-icon text-warning btn-spam-comment" title="Đánh dấu spam" data-url="' . $spamUrl . '">';
                        $html .= '<i class="bx bx-shield-x"></i>';
                        $html .= '</button>';
                    }

                    if ($row->status !== Comment::STATUS_TRASH) {
                        $html .= '<button type="button" class="btn btn-sm btn-icon text-secondary btn-trash-comment" title="Chuyển vào thùng rác" data-url="' . $trashUrl . '">';
                        $html .= '<i class="bx bx-trash"></i>';
                        $html .= '</button>';
                    }
                }

                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon text-danger btn-delete" title="Xóa" data-url="' . $deleteUrl . '" data-title="Bình luận #' . $row->id . '">';
                    $html .= '<i class="bx bx-trash-alt"></i>';
                    $html .= '</button>';
                }

                $html .= '</div>';

                return $html ?: '<span class="text-muted">—</span>';
            })
            ->rawColumns(['checkbox_html', 'user_html', 'post_html', 'content_html', 'status_html', 'created_at_html', 'action_html'])
            ->make(true);
    }

    /**
     * Grid data for trashed comments (soft deleted)
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function gridTrashedData()
    {
        $query = $this->model::onlyTrashed();
        $query->select([
            'comments.id',
            'comments.post_id',
            'comments.user_id',
            'comments.content',
            'comments.status',
            'comments.parent_id',
            'comments.created_at',
            'comments.deleted_at',
        ])
        ->with(['user', 'post', 'parent']);

        return $query;
    }

    /**
     * Filter trashed data for DataTables
     *
     * @param \Illuminate\Database\Eloquent\Builder $grid
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filterTrashedData($grid)
    {
        $request = request();
        $search = $request->input('search.value');
        $deletedAt = $request->input('deleted_at');

        if ($search) {
            $grid->where(function ($query) use ($search) {
                $query->where('comments.content', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('full_name', 'like', '%' . $search . '%')
                          ->orWhere('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('post', function ($q) use ($search) {
                        $q->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($deletedAt) {
            $date = \DateTime::createFromFormat('d/m/Y', $deletedAt);
            $formattedDate = $date ? $date->format('Y-m-d') : null;
            if ($formattedDate) {
                $grid->whereDate('comments.deleted_at', $formattedDate);
            }
        }

        $grid->orderBy('comments.deleted_at', 'desc');

        return $grid;
    }

    /**
     * Render DataTables for trashed comments
     *
     * @param \Illuminate\Database\Eloquent\Builder $data
     * @return mixed
     */
    public function renderTrashedDataTables($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('checkbox_html', function ($row) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '" />';
            })
            ->addColumn('user_html', function ($row) {
                $user = $row->user;
                if (!$user) {
                    return '<span class="text-muted">—</span>';
                }

                $name = $user->full_name ?? $user->email;
                $avatar = $user->avatar ? thumb_path($user->avatar) : null;

                $html = '<div class="d-flex align-items-center">';
                if ($avatar) {
                    $html .= '<img src="' . $avatar . '" alt="' . htmlspecialchars($name) . '" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;" />';
                } else {
                    $html .= '<div class="avatar-initial rounded-circle bg-label-primary me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">';
                    $html .= '<span class="text-xs">' . strtoupper(substr($name, 0, 1)) . '</span>';
                    $html .= '</div>';
                }
                $html .= '<div class="d-flex flex-column">';
                $html .= '<span class="text-body fw-semibold">' . htmlspecialchars($name) . '</span>';
                $html .= '<small class="text-muted">' . htmlspecialchars($user->email) . '</small>';
                $html .= '</div>';
                $html .= '</div>';

                return $html;
            })
            ->addColumn('post_html', function ($row) {
                $post = $row->post;
                if (!$post) {
                    return '<span class="text-muted">—</span>';
                }

                $title = Str::limit($post->title, 50);
                $url = route('client.post', ['slug' => $post->slug]);

                return '<a href="' . $url . '" target="_blank" class="text-body fw-semibold" title="' . htmlspecialchars($post->title) . '">' . htmlspecialchars($title) . '</a>';
            })
            ->addColumn('content_html', function ($row) {
                $content = Str::limit(strip_tags($row->content), 100);
                $isReply = $row->parent_id !== null;
                $html = '';

                if ($isReply && $row->parent) {
                    $html .= '<div class="mb-2">';
                    $html .= '<small class="text-muted">Trả lời: <strong>' . htmlspecialchars(Str::limit(strip_tags($row->parent->content), 50)) . '</strong></small>';
                    $html .= '</div>';
                }

                $html .= '<div class="text-body">' . nl2br(htmlspecialchars($content)) . '</div>';

                return $html;
            })
            ->addColumn('status_html', function ($row) {
                $statusLabels = $this->getStatusLabel();
                $statusLabel = $statusLabels[$row->status] ?? $row->status;

                $badgeClass = match ($row->status) {
                    Comment::STATUS_APPROVED => 'bg-label-success',
                    Comment::STATUS_PENDING => 'bg-label-warning',
                    Comment::STATUS_SPAM => 'bg-label-danger',
                    Comment::STATUS_TRASH => 'bg-label-secondary',
                    default => 'bg-label-secondary',
                };

                return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($statusLabel) . '</span>';
            })
            ->addColumn('deleted_at_html', function ($row) {
                return '<span class="text-muted">' . $row->deleted_at->format('d/m/Y H:i') . '</span>';
            })
            ->addColumn('action_html', function ($row) {
                $restoreUrl = route('admin.comments.restore', $row->id);
                $forceDeleteUrl = route('admin.comments.forceDelete', $row->id);

                $canUpdate = auth()->user()->can('comment.update');
                $canDelete = auth()->user()->can('comment.delete');

                $html = '<div class="d-inline-block text-nowrap">';

                if ($canUpdate) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon btn-success btn-restore-comment" title="Khôi phục" data-url="' . $restoreUrl . '">';
                    $html .= '<i class="bx bx-undo"></i>';
                    $html .= '</button>';
                }

                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-icon text-danger btn-force-delete-comment" title="Xóa vĩnh viễn" data-url="' . $forceDeleteUrl . '" data-title="Bình luận #' . $row->id . '">';
                    $html .= '<i class="bx bx-trash-alt"></i>';
                    $html .= '</button>';
                }

                $html .= '</div>';

                return $html ?: '<span class="text-muted">—</span>';
            })
            ->rawColumns(['checkbox_html', 'user_html', 'post_html', 'content_html', 'status_html', 'deleted_at_html', 'action_html'])
            ->make(true);
    }

    /**
     * Restore a trashed comment
     *
     * @param int $id
     * @return bool
     */
    public function restore($id)
    {
        $comment = $this->model::onlyTrashed()->find($id);
        if ($comment && $comment->trashed()) {
            return $comment->restore();
        }
        return false;
    }

    /**
     * Force delete a comment
     *
     * @param int $id
     * @return bool
     */
    public function forceDelete($id)
    {
        $comment = $this->model::onlyTrashed()->find($id);
        if ($comment && $comment->trashed()) {
            return $comment->forceDelete();
        }
        return false;
    }

    /**
     * Bulk restore comments
     *
     * @param array $ids
     * @return int
     */
    public function bulkRestore(array $ids)
    {
        return $this->model::onlyTrashed()
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at')
            ->restore();
    }

    /**
     * Bulk force delete comments
     *
     * @param array $ids
     * @return int
     */
    public function bulkForceDelete(array $ids)
    {
        return $this->model::onlyTrashed()
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at')
            ->forceDelete();
    }

    /**
     * Bulk delete comments (soft delete)
     *
     * @param array $ids
     * @return int
     */
    public function bulkDelete(array $ids)
    {
        return $this->model::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->delete();
    }

    /**
     * Bulk change status
     *
     * @param array $ids
     * @param string $status
     * @return int
     */
    public function bulkChangeStatus(array $ids, string $status)
    {
        if ($status === Comment::STATUS_TRASH) {
            // If changing to trash, soft delete the comments
            return $this->model::whereIn('id', $ids)
                ->whereNull('deleted_at')
                ->delete(); // Soft delete
        } else {
            // For other statuses, update status and restore if needed
            $comments = $this->model::withTrashed()->whereIn('id', $ids)->get();
            $count = 0;
            
            foreach ($comments as $comment) {
                if ($comment->trashed()) {
                    $comment->restore();
                }
                $comment->status = $status;
                $comment->save();
                $count++;
            }
            
            return $count;
        }
    }
}

